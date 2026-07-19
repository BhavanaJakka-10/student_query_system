-- =============================================================================
-- EXAMINATION PORTAL — PRODUCTION DATABASE SCHEMA
-- =============================================================================
-- Target RDBMS       : PostgreSQL 14+
-- File               : examination_portal.sql
-- Created            : 2026-07-18
-- Description        : Self-contained, idempotent DDL for the Study Material
--                      Sharing and Practice Portal (Examination Portal).
--
-- Postgres-specific features used:
--   • CREATE TYPE ... AS ENUM          (custom enum types)
--   • tsvector / tsquery / GIN index   (full-text search)
--   • JSONB                            (notification payloads)
--   • PL/pgSQL functions & triggers    (updated_at, versioning, notifications)
--   • SELECT ... FOR UPDATE SKIP LOCKED(safe queue worker dequeue)
--   • GENERATED ALWAYS AS IDENTITY     (identity columns)
--   • RANGE partitioning hints         (commented, for audit/archive tables)
--
-- Security assumptions:
--   • password_hash is populated by the application layer using bcrypt or
--     Argon2id.  This schema stores hashes only; it never stores plaintext.
--   • The application should connect via a least-privilege role (e.g.,
--     exam_portal_app) with GRANT SELECT, INSERT, UPDATE, DELETE on the
--     relevant tables.  DDL privileges should stay with the migration role.
--   • Row-Level Security (RLS) is NOT enabled here but is recommended for
--     multi-tenant or fine-grained access control in production.
--
-- Migration / scale recommendations:
--   • For tables exceeding ~50 M rows (audit_logs, student_records_archive),
--     convert to RANGE-partitioned tables on created_at.
--   • Consider pg_partman for automated partition management.
--   • Add connection-pooling (PgBouncer) and read replicas for heavy
--     dashboard reads.
--   • Monitor index bloat with pg_stat_user_indexes; schedule REINDEX
--     CONCURRENTLY during maintenance windows.
-- =============================================================================

BEGIN;

-- ─────────────────────────────────────────────────────────────────────────────
-- 0.  EXTENSIONS
-- ─────────────────────────────────────────────────────────────────────────────
CREATE EXTENSION IF NOT EXISTS "pgcrypto";       -- gen_random_uuid()
CREATE EXTENSION IF NOT EXISTS "pg_trgm";        -- trigram similarity for LIKE searches

-- ─────────────────────────────────────────────────────────────────────────────
-- 1.  ENUM / CUSTOM TYPES
-- ─────────────────────────────────────────────────────────────────────────────

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'user_role') THEN
        CREATE TYPE user_role AS ENUM ('student', 'staff', 'admin');
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'account_status') THEN
        CREATE TYPE account_status AS ENUM ('active', 'inactive', 'suspended', 'pending');
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'gender_type') THEN
        CREATE TYPE gender_type AS ENUM ('male', 'female', 'other', 'prefer_not_to_say');
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'difficulty_level') THEN
        CREATE TYPE difficulty_level AS ENUM ('easy', 'medium', 'hard', 'expert');
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'question_type') THEN
        CREATE TYPE question_type AS ENUM ('mcq', 'short_answer', 'long_answer', 'true_false', 'fill_blank', 'coding');
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'enrollment_status') THEN
        CREATE TYPE enrollment_status AS ENUM ('active', 'completed', 'dropped', 'withdrawn');
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'notification_status') THEN
        CREATE TYPE notification_status AS ENUM ('pending', 'in_flight', 'sent', 'failed');
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'query_status') THEN
        CREATE TYPE query_status AS ENUM ('open', 'in_progress', 'resolved', 'closed');
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'attempt_status') THEN
        CREATE TYPE attempt_status AS ENUM ('in_progress', 'submitted', 'graded', 'timed_out');
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'audit_action') THEN
        CREATE TYPE audit_action AS ENUM ('INSERT', 'UPDATE', 'DELETE');
    END IF;
END $$;


-- ─────────────────────────────────────────────────────────────────────────────
-- 2.  LOOKUP TABLES
-- ─────────────────────────────────────────────────────────────────────────────

-- Branches / Departments
CREATE TABLE IF NOT EXISTS branches (
    id              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name            VARCHAR(150)    NOT NULL,
    code            VARCHAR(20)     NOT NULL UNIQUE,
    is_deleted      BOOLEAN         NOT NULL DEFAULT FALSE,
    deleted_at      TIMESTAMPTZ,
    created_at      TIMESTAMPTZ     NOT NULL DEFAULT now(),
    updated_at      TIMESTAMPTZ     NOT NULL DEFAULT now()
);

-- Semesters
CREATE TABLE IF NOT EXISTS semesters (
    id              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    number          SMALLINT        NOT NULL CHECK (number BETWEEN 1 AND 12),
    label           VARCHAR(30),         -- e.g. 'Semester 1'
    is_deleted      BOOLEAN         NOT NULL DEFAULT FALSE,
    deleted_at      TIMESTAMPTZ,
    created_at      TIMESTAMPTZ     NOT NULL DEFAULT now(),
    updated_at      TIMESTAMPTZ     NOT NULL DEFAULT now(),
    UNIQUE (number)
);


-- ─────────────────────────────────────────────────────────────────────────────
-- 3.  CORE USER & AUTH TABLES
-- ─────────────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS users (
    id              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    -- kept as user_id alias via a generated column for legacy PHP compat
    email           VARCHAR(255)    NOT NULL,
    name            VARCHAR(200)    NOT NULL,
    password_hash   VARCHAR(255)    NOT NULL,   -- bcrypt/argon2 hash
    salt            VARCHAR(128),               -- optional; modern algos embed salt
    role            user_role       NOT NULL DEFAULT 'student',
    status          account_status  NOT NULL DEFAULT 'active',
    remember_token  VARCHAR(128),
    email_verified_at TIMESTAMPTZ,
    last_login_at   TIMESTAMPTZ,
    is_deleted      BOOLEAN         NOT NULL DEFAULT FALSE,
    deleted_at      TIMESTAMPTZ,
    created_at      TIMESTAMPTZ     NOT NULL DEFAULT now(),
    updated_at      TIMESTAMPTZ     NOT NULL DEFAULT now(),
    CONSTRAINT uq_users_email UNIQUE (email)
);

COMMENT ON TABLE  users IS 'Central user account table for all roles.';
COMMENT ON COLUMN users.password_hash IS 'Application-layer bcrypt/argon2id hash. Never store plaintext.';

-- Roles lookup (for RBAC beyond the enum)
CREATE TABLE IF NOT EXISTS roles (
    id              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name            VARCHAR(60)     NOT NULL UNIQUE,
    description     TEXT,
    is_system       BOOLEAN         NOT NULL DEFAULT FALSE,  -- cannot delete system roles
    created_at      TIMESTAMPTZ     NOT NULL DEFAULT now(),
    updated_at      TIMESTAMPTZ     NOT NULL DEFAULT now()
);

-- Role permissions (granular RBAC)
CREATE TABLE IF NOT EXISTS role_permissions (
    id              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    role_id         BIGINT          NOT NULL REFERENCES roles(id) ON DELETE CASCADE,
    permission      VARCHAR(120)    NOT NULL,   -- e.g. 'course.create', 'question.delete'
    created_at      TIMESTAMPTZ     NOT NULL DEFAULT now(),
    UNIQUE (role_id, permission)
);


-- ─────────────────────────────────────────────────────────────────────────────
-- 4.  PROFILE TABLES
-- ─────────────────────────────────────────────────────────────────────────────

-- Student personal profile (matches existing student_profile table in PHP)
CREATE TABLE IF NOT EXISTS student_profiles (
    id              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    user_id         BIGINT          NOT NULL UNIQUE REFERENCES users(id) ON DELETE CASCADE,
    branch_id       BIGINT          REFERENCES branches(id) ON UPDATE CASCADE ON DELETE SET NULL,
    semester_id     BIGINT          REFERENCES semesters(id) ON UPDATE CASCADE ON DELETE SET NULL,
    enrollment_no   VARCHAR(40)     UNIQUE,
    dob             DATE,
    gender          gender_type,
    blood_group     VARCHAR(5),
    mobile          VARCHAR(20),
    aadhaar_no      VARCHAR(20),
    abc_id          VARCHAR(40),
    address         TEXT,
    city            VARCHAR(80),
    state           VARCHAR(80),
    pincode         VARCHAR(10),
    father_name     VARCHAR(150),
    father_mobile   VARCHAR(20),
    mother_name     VARCHAR(150),
    mother_mobile   VARCHAR(20),
    guardian_name   VARCHAR(150),
    guardian_relation VARCHAR(50),
    guardian_mobile VARCHAR(20),
    guardian_email  VARCHAR(255),
    guardian_occupation VARCHAR(100),
    medical_condition TEXT,
    emergency_contact VARCHAR(20),
    photo           VARCHAR(255),
    aadhaar_file    VARCHAR(255),
    pan_file        VARCHAR(255),
    ssc_file        VARCHAR(255),
    hsc_file        VARCHAR(255),
    lc_file         VARCHAR(255),
    caste_file      VARCHAR(255),
    income_file     VARCHAR(255),
    domicile_file   VARCHAR(255),
    receipt_file    VARCHAR(255),
    is_deleted      BOOLEAN         NOT NULL DEFAULT FALSE,
    deleted_at      TIMESTAMPTZ,
    created_at      TIMESTAMPTZ     NOT NULL DEFAULT now(),
    updated_at      TIMESTAMPTZ     NOT NULL DEFAULT now()
);

-- Student academic details (matches existing student_academic table in PHP)
CREATE TABLE IF NOT EXISTS student_academics (
    id              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    user_id         BIGINT          NOT NULL UNIQUE REFERENCES users(id) ON DELETE CASCADE,
    ssc_percentage  NUMERIC(5,2),
    hsc_percentage  NUMERIC(5,2),
    diploma_percentage NUMERIC(5,2),
    cet_score       NUMERIC(7,2),
    jee_score       NUMERIC(7,2),
    current_cgpa    NUMERIC(4,2),
    backlogs        SMALLINT        DEFAULT 0,
    admission_year  SMALLINT,
    is_deleted      BOOLEAN         NOT NULL DEFAULT FALSE,
    deleted_at      TIMESTAMPTZ,
    created_at      TIMESTAMPTZ     NOT NULL DEFAULT now(),
    updated_at      TIMESTAMPTZ     NOT NULL DEFAULT now()
);

-- Staff profile
CREATE TABLE IF NOT EXISTS staff_profiles (
    id              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    user_id         BIGINT          NOT NULL UNIQUE REFERENCES users(id) ON DELETE CASCADE,
    employee_id     VARCHAR(40)     UNIQUE,
    department      VARCHAR(150),
    designation     VARCHAR(100),
    qualification   VARCHAR(200),
    specialization  VARCHAR(200),
    experience_years SMALLINT       DEFAULT 0,
    phone           VARCHAR(20),
    office_location VARCHAR(100),
    bio             TEXT,
    photo           VARCHAR(255),
    is_deleted      BOOLEAN         NOT NULL DEFAULT FALSE,
    deleted_at      TIMESTAMPTZ,
    created_at      TIMESTAMPTZ     NOT NULL DEFAULT now(),
    updated_at      TIMESTAMPTZ     NOT NULL DEFAULT now()
);


-- ─────────────────────────────────────────────────────────────────────────────
-- 5.  COURSES & ENROLLMENTS
-- ─────────────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS courses (
    id              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    code            VARCHAR(30)     NOT NULL UNIQUE,
    name            VARCHAR(200)    NOT NULL,
    description     TEXT,
    branch_id       BIGINT          REFERENCES branches(id) ON UPDATE CASCADE ON DELETE SET NULL,
    semester_id     BIGINT          REFERENCES semesters(id) ON UPDATE CASCADE ON DELETE SET NULL,
    credits         SMALLINT        NOT NULL DEFAULT 3 CHECK (credits > 0),
    staff_id        BIGINT          REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL,
    is_active       BOOLEAN         NOT NULL DEFAULT TRUE,
    is_deleted      BOOLEAN         NOT NULL DEFAULT FALSE,
    deleted_at      TIMESTAMPTZ,
    created_at      TIMESTAMPTZ     NOT NULL DEFAULT now(),
    updated_at      TIMESTAMPTZ     NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS course_enrollments (
    id              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    student_id      BIGINT          NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    course_id       BIGINT          NOT NULL REFERENCES courses(id) ON DELETE CASCADE,
    status          enrollment_status NOT NULL DEFAULT 'active',
    enrolled_at     TIMESTAMPTZ     NOT NULL DEFAULT now(),
    completed_at    TIMESTAMPTZ,
    grade           VARCHAR(5),
    is_deleted      BOOLEAN         NOT NULL DEFAULT FALSE,
    deleted_at      TIMESTAMPTZ,
    created_at      TIMESTAMPTZ     NOT NULL DEFAULT now(),
    updated_at      TIMESTAMPTZ     NOT NULL DEFAULT now()
);

-- Partial unique index: prevent duplicate ACTIVE enrollments for same student+course
CREATE UNIQUE INDEX IF NOT EXISTS uq_active_enrollment
    ON course_enrollments (student_id, course_id)
    WHERE status = 'active' AND is_deleted = FALSE;


-- ─────────────────────────────────────────────────────────────────────────────
-- 6.  SYLLABUS & LABS
-- ─────────────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS syllabus (
    id              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    course_id       BIGINT          NOT NULL REFERENCES courses(id) ON DELETE CASCADE,
    title           VARCHAR(300)    NOT NULL,
    unit_number     SMALLINT,
    description     TEXT,
    topics          TEXT,           -- semicolon or newline delimited list
    file_path       VARCHAR(500),
    file_type       VARCHAR(20),
    uploaded_by     BIGINT          REFERENCES users(id) ON DELETE SET NULL,
    is_deleted      BOOLEAN         NOT NULL DEFAULT FALSE,
    deleted_at      TIMESTAMPTZ,
    created_at      TIMESTAMPTZ     NOT NULL DEFAULT now(),
    updated_at      TIMESTAMPTZ     NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS compilers (
    id              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name            VARCHAR(60)     NOT NULL UNIQUE,  -- 'C', 'C++', 'Java', 'Python', 'PHP', 'SQL'
    language_key    VARCHAR(30)     NOT NULL UNIQUE,  -- 'c', 'cpp', 'java', 'python3', 'php', 'sql'
    version         VARCHAR(30),
    is_active       BOOLEAN         NOT NULL DEFAULT TRUE,
    created_at      TIMESTAMPTZ     NOT NULL DEFAULT now(),
    updated_at      TIMESTAMPTZ     NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS labs (
    id              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    course_id       BIGINT          NOT NULL REFERENCES courses(id) ON DELETE CASCADE,
    title           VARCHAR(300)    NOT NULL,
    description     TEXT,
    instructions    TEXT,
    compiler_id     BIGINT          REFERENCES compilers(id) ON DELETE SET NULL,
    starter_code    TEXT,
    expected_output TEXT,
    difficulty      difficulty_level NOT NULL DEFAULT 'medium',
    sort_order      SMALLINT        NOT NULL DEFAULT 0,
    uploaded_by     BIGINT          REFERENCES users(id) ON DELETE SET NULL,
    is_deleted      BOOLEAN         NOT NULL DEFAULT FALSE,
    deleted_at      TIMESTAMPTZ,
    created_at      TIMESTAMPTZ     NOT NULL DEFAULT now(),
    updated_at      TIMESTAMPTZ     NOT NULL DEFAULT now()
);


-- ─────────────────────────────────────────────────────────────────────────────
-- 7.  QUESTION BANK & VERSIONING
-- ─────────────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS question_bank (
    id              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    course_id       BIGINT          NOT NULL REFERENCES courses(id) ON DELETE CASCADE,
    question_text   TEXT            NOT NULL,
    answer_text     TEXT,
    explanation     TEXT,
    type            question_type   NOT NULL DEFAULT 'mcq',
    difficulty      difficulty_level NOT NULL DEFAULT 'medium',
    marks           SMALLINT        NOT NULL DEFAULT 1 CHECK (marks > 0),
    tags            TEXT[],         -- Postgres array; e.g. '{"DBMS","SQL","Joins"}'
    options         JSONB,          -- for MCQs: [{"key":"A","text":"...","is_correct":true}, ...]
    version         INTEGER         NOT NULL DEFAULT 1,
    created_by      BIGINT          REFERENCES users(id) ON DELETE SET NULL,
    -- Full-text search vector (Postgres-specific)
    search_vector   tsvector,
    is_deleted      BOOLEAN         NOT NULL DEFAULT FALSE,
    deleted_at      TIMESTAMPTZ,
    created_at      TIMESTAMPTZ     NOT NULL DEFAULT now(),
    updated_at      TIMESTAMPTZ     NOT NULL DEFAULT now()
);

-- Question version history
CREATE TABLE IF NOT EXISTS question_versions (
    id              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    question_id     BIGINT          NOT NULL REFERENCES question_bank(id) ON DELETE CASCADE,
    version         INTEGER         NOT NULL,
    question_text   TEXT            NOT NULL,
    answer_text     TEXT,
    explanation     TEXT,
    type            question_type   NOT NULL,
    difficulty      difficulty_level NOT NULL,
    marks           SMALLINT        NOT NULL,
    tags            TEXT[],
    options         JSONB,
    changed_by      BIGINT          REFERENCES users(id) ON DELETE SET NULL,
    changed_at      TIMESTAMPTZ     NOT NULL DEFAULT now(),
    UNIQUE (question_id, version)
);


-- ─────────────────────────────────────────────────────────────────────────────
-- 8.  PRACTICE QUESTIONS
-- ─────────────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS practice_questions (
    id              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    course_id       BIGINT          NOT NULL REFERENCES courses(id) ON DELETE CASCADE,
    question_text   TEXT            NOT NULL,
    answer_text     TEXT,
    explanation     TEXT,
    type            question_type   NOT NULL DEFAULT 'short_answer',
    difficulty      difficulty_level NOT NULL DEFAULT 'medium',
    tags            TEXT[],
    options         JSONB,
    source          VARCHAR(200),      -- 'Previous Year 2024', 'Model Paper', etc.
    year            SMALLINT,
    marks           SMALLINT        NOT NULL DEFAULT 2 CHECK (marks > 0),
    search_vector   tsvector,
    created_by      BIGINT          REFERENCES users(id) ON DELETE SET NULL,
    is_deleted      BOOLEAN         NOT NULL DEFAULT FALSE,
    deleted_at      TIMESTAMPTZ,
    created_at      TIMESTAMPTZ     NOT NULL DEFAULT now(),
    updated_at      TIMESTAMPTZ     NOT NULL DEFAULT now()
);


-- ─────────────────────────────────────────────────────────────────────────────
-- 9.  STUDENT RECORDS & ATTEMPTS
-- ─────────────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS student_records (
    id              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    student_id      BIGINT          NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    course_id       BIGINT          NOT NULL REFERENCES courses(id) ON DELETE CASCADE,
    exam_type       VARCHAR(60)     NOT NULL DEFAULT 'internal',  -- 'internal','external','lab','assignment'
    marks_obtained  NUMERIC(6,2),
    marks_total     NUMERIC(6,2),
    grade           VARCHAR(5),
    semester_id     BIGINT          REFERENCES semesters(id) ON DELETE SET NULL,
    exam_date       DATE,
    remarks         TEXT,
    is_deleted      BOOLEAN         NOT NULL DEFAULT FALSE,
    deleted_at      TIMESTAMPTZ,
    created_at      TIMESTAMPTZ     NOT NULL DEFAULT now(),
    updated_at      TIMESTAMPTZ     NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS student_attempts (
    id              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    student_id      BIGINT          NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    question_id     BIGINT          REFERENCES question_bank(id) ON DELETE SET NULL,
    practice_question_id BIGINT     REFERENCES practice_questions(id) ON DELETE SET NULL,
    answer_given    TEXT,
    is_correct      BOOLEAN,
    score           NUMERIC(6,2)    DEFAULT 0,
    time_spent_sec  INTEGER         DEFAULT 0,
    status          attempt_status  NOT NULL DEFAULT 'submitted',
    feedback        TEXT,
    attempted_at    TIMESTAMPTZ     NOT NULL DEFAULT now(),
    created_at      TIMESTAMPTZ     NOT NULL DEFAULT now(),
    CHECK (question_id IS NOT NULL OR practice_question_id IS NOT NULL)
);


-- ─────────────────────────────────────────────────────────────────────────────
-- 10. QUERIES (Student queries / doubts raised to staff)
-- ─────────────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS queries (
    id              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    student_id      BIGINT          NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    course_id       BIGINT          REFERENCES courses(id) ON DELETE SET NULL,
    subject         VARCHAR(300)    NOT NULL,
    message         TEXT            NOT NULL,
    status          query_status    NOT NULL DEFAULT 'open',
    assigned_to     BIGINT          REFERENCES users(id) ON DELETE SET NULL,
    priority        SMALLINT        NOT NULL DEFAULT 0 CHECK (priority BETWEEN 0 AND 5),
    is_deleted      BOOLEAN         NOT NULL DEFAULT FALSE,
    deleted_at      TIMESTAMPTZ,
    created_at      TIMESTAMPTZ     NOT NULL DEFAULT now(),
    updated_at      TIMESTAMPTZ     NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS query_replies (
    id              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    query_id        BIGINT          NOT NULL REFERENCES queries(id) ON DELETE CASCADE,
    replied_by      BIGINT          NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    message         TEXT            NOT NULL,
    attachment_path VARCHAR(500),
    created_at      TIMESTAMPTZ     NOT NULL DEFAULT now()
);


-- ─────────────────────────────────────────────────────────────────────────────
-- 11. NOTIFICATION QUEUE
-- ─────────────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS notifications_queue (
    id              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    channel         VARCHAR(60)     NOT NULL DEFAULT 'in_app',  -- 'in_app', 'email', 'push'
    recipient_id    BIGINT          REFERENCES users(id) ON DELETE CASCADE,
    subject         VARCHAR(300),
    payload         JSONB           NOT NULL DEFAULT '{}',
    status          notification_status NOT NULL DEFAULT 'pending',
    attempts        SMALLINT        NOT NULL DEFAULT 0,
    max_attempts    SMALLINT        NOT NULL DEFAULT 3,
    run_after       TIMESTAMPTZ     NOT NULL DEFAULT now(),
    last_attempt_at TIMESTAMPTZ,
    sent_at         TIMESTAMPTZ,
    error_message   TEXT,
    created_at      TIMESTAMPTZ     NOT NULL DEFAULT now(),
    updated_at      TIMESTAMPTZ     NOT NULL DEFAULT now()
);

COMMENT ON TABLE notifications_queue IS 'Transactional notification outbox. Workers dequeue with SELECT ... FOR UPDATE SKIP LOCKED.';


-- ─────────────────────────────────────────────────────────────────────────────
-- 12. AUDIT LOGS
-- ─────────────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS audit_logs (
    id              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    table_name      VARCHAR(100)    NOT NULL,
    record_id       BIGINT,
    action          audit_action    NOT NULL,
    old_data        JSONB,
    new_data        JSONB,
    changed_by      BIGINT          REFERENCES users(id) ON DELETE SET NULL,
    ip_address      INET,
    user_agent      TEXT,
    created_at      TIMESTAMPTZ     NOT NULL DEFAULT now()
);

-- Partitioning hint (activate when table grows large):
-- CREATE TABLE audit_logs (
--     ...
-- ) PARTITION BY RANGE (created_at);
-- CREATE TABLE audit_logs_2026_q1 PARTITION OF audit_logs
--     FOR VALUES FROM ('2026-01-01') TO ('2026-04-01');


-- ─────────────────────────────────────────────────────────────────────────────
-- 13. SETTINGS
-- ─────────────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS settings (
    id              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    key             VARCHAR(120)    NOT NULL UNIQUE,
    value           TEXT,
    description     TEXT,
    is_public       BOOLEAN         NOT NULL DEFAULT FALSE,
    updated_by      BIGINT          REFERENCES users(id) ON DELETE SET NULL,
    created_at      TIMESTAMPTZ     NOT NULL DEFAULT now(),
    updated_at      TIMESTAMPTZ     NOT NULL DEFAULT now()
);


-- ─────────────────────────────────────────────────────────────────────────────
-- 14. ARCHIVE TABLES (for large historical data)
-- ─────────────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS student_records_archive (
    id              BIGINT          NOT NULL,
    student_id      BIGINT          NOT NULL,
    course_id       BIGINT          NOT NULL,
    exam_type       VARCHAR(60),
    marks_obtained  NUMERIC(6,2),
    marks_total     NUMERIC(6,2),
    grade           VARCHAR(5),
    semester_id     BIGINT,
    exam_date       DATE,
    remarks         TEXT,
    archived_at     TIMESTAMPTZ     NOT NULL DEFAULT now(),
    original_created_at TIMESTAMPTZ,
    PRIMARY KEY (id, archived_at)
);
-- Partitioning hint:
-- ) PARTITION BY RANGE (archived_at);

CREATE TABLE IF NOT EXISTS audit_logs_archive (
    id              BIGINT          NOT NULL,
    table_name      VARCHAR(100)    NOT NULL,
    record_id       BIGINT,
    action          audit_action    NOT NULL,
    old_data        JSONB,
    new_data        JSONB,
    changed_by      BIGINT,
    ip_address      INET,
    user_agent      TEXT,
    original_created_at TIMESTAMPTZ,
    archived_at     TIMESTAMPTZ     NOT NULL DEFAULT now(),
    PRIMARY KEY (id, archived_at)
);


-- ─────────────────────────────────────────────────────────────────────────────
-- 15. STUDY MATERIAL (uploads tracking)
-- ─────────────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS study_materials (
    id              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    course_id       BIGINT          NOT NULL REFERENCES courses(id) ON DELETE CASCADE,
    title           VARCHAR(300)    NOT NULL,
    description     TEXT,
    file_path       VARCHAR(500)    NOT NULL,
    file_type       VARCHAR(20),       -- 'pdf', 'ppt', 'doc', 'zip', 'image', 'video'
    file_size_bytes BIGINT,
    uploaded_by     BIGINT          REFERENCES users(id) ON DELETE SET NULL,
    download_count  INTEGER         NOT NULL DEFAULT 0,
    is_deleted      BOOLEAN         NOT NULL DEFAULT FALSE,
    deleted_at      TIMESTAMPTZ,
    created_at      TIMESTAMPTZ     NOT NULL DEFAULT now(),
    updated_at      TIMESTAMPTZ     NOT NULL DEFAULT now()
);

-- Previous year papers (separate from generic study materials)
CREATE TABLE IF NOT EXISTS previous_papers (
    id              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    course_id       BIGINT          NOT NULL REFERENCES courses(id) ON DELETE CASCADE,
    title           VARCHAR(300)    NOT NULL,
    exam_year       SMALLINT        NOT NULL,
    exam_type       VARCHAR(60)     DEFAULT 'university',
    file_path       VARCHAR(500)    NOT NULL,
    file_type       VARCHAR(20),
    uploaded_by     BIGINT          REFERENCES users(id) ON DELETE SET NULL,
    download_count  INTEGER         NOT NULL DEFAULT 0,
    is_deleted      BOOLEAN         NOT NULL DEFAULT FALSE,
    deleted_at      TIMESTAMPTZ,
    created_at      TIMESTAMPTZ     NOT NULL DEFAULT now(),
    updated_at      TIMESTAMPTZ     NOT NULL DEFAULT now()
);


-- =============================================================================
-- 16. INDEXES
-- =============================================================================

-- Authentication & user lookups
CREATE INDEX IF NOT EXISTS idx_users_email           ON users (email)              WHERE is_deleted = FALSE;
CREATE INDEX IF NOT EXISTS idx_users_role            ON users (role)               WHERE is_deleted = FALSE;
CREATE INDEX IF NOT EXISTS idx_users_status          ON users (status)             WHERE is_deleted = FALSE;
CREATE INDEX IF NOT EXISTS idx_users_remember_token  ON users (remember_token)     WHERE remember_token IS NOT NULL;

-- Profile lookups
CREATE INDEX IF NOT EXISTS idx_student_profiles_user ON student_profiles (user_id);
CREATE INDEX IF NOT EXISTS idx_student_profiles_branch ON student_profiles (branch_id);
CREATE INDEX IF NOT EXISTS idx_staff_profiles_user   ON staff_profiles (user_id);

-- Course lookups
CREATE INDEX IF NOT EXISTS idx_courses_branch_sem    ON courses (branch_id, semester_id) WHERE is_deleted = FALSE;
CREATE INDEX IF NOT EXISTS idx_courses_staff         ON courses (staff_id)         WHERE is_deleted = FALSE;
CREATE INDEX IF NOT EXISTS idx_course_enrollments_student ON course_enrollments (student_id) WHERE is_deleted = FALSE;
CREATE INDEX IF NOT EXISTS idx_course_enrollments_course  ON course_enrollments (course_id)  WHERE is_deleted = FALSE;

-- Question bank: full-text search (GIN on tsvector)
CREATE INDEX IF NOT EXISTS idx_question_bank_search  ON question_bank USING GIN (search_vector);
CREATE INDEX IF NOT EXISTS idx_question_bank_tags    ON question_bank USING GIN (tags);
CREATE INDEX IF NOT EXISTS idx_question_bank_course  ON question_bank (course_id)  WHERE is_deleted = FALSE;
CREATE INDEX IF NOT EXISTS idx_question_bank_diff    ON question_bank (difficulty) WHERE is_deleted = FALSE;

-- Practice questions: full-text search
CREATE INDEX IF NOT EXISTS idx_practice_q_search     ON practice_questions USING GIN (search_vector);
CREATE INDEX IF NOT EXISTS idx_practice_q_tags       ON practice_questions USING GIN (tags);
CREATE INDEX IF NOT EXISTS idx_practice_q_course     ON practice_questions (course_id) WHERE is_deleted = FALSE;

-- Notification queue: worker dequeue index
CREATE INDEX IF NOT EXISTS idx_notif_queue_pending    ON notifications_queue (run_after)
    WHERE status = 'pending' AND attempts < max_attempts;

-- Audit logs: table + time lookup
CREATE INDEX IF NOT EXISTS idx_audit_logs_table      ON audit_logs (table_name, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_audit_logs_record     ON audit_logs (table_name, record_id);

-- Student records
CREATE INDEX IF NOT EXISTS idx_student_records_student ON student_records (student_id) WHERE is_deleted = FALSE;
CREATE INDEX IF NOT EXISTS idx_student_records_course  ON student_records (course_id)  WHERE is_deleted = FALSE;

-- Queries
CREATE INDEX IF NOT EXISTS idx_queries_student       ON queries (student_id)       WHERE is_deleted = FALSE;
CREATE INDEX IF NOT EXISTS idx_queries_status         ON queries (status)           WHERE is_deleted = FALSE;

-- Study materials
CREATE INDEX IF NOT EXISTS idx_study_materials_course ON study_materials (course_id) WHERE is_deleted = FALSE;

-- Syllabus
CREATE INDEX IF NOT EXISTS idx_syllabus_course        ON syllabus (course_id)       WHERE is_deleted = FALSE;


-- =============================================================================
-- 17. FUNCTIONS & TRIGGERS (PL/pgSQL)
-- =============================================================================

-- ─────────────────────────────────────────────────────────────────────────────
-- 17a. Generic updated_at trigger
-- ─────────────────────────────────────────────────────────────────────────────

CREATE OR REPLACE FUNCTION fn_set_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at := now();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Apply updated_at trigger to every table that has the column
DO $$
DECLARE
    tbl TEXT;
BEGIN
    FOR tbl IN
        SELECT table_name
        FROM information_schema.columns
        WHERE column_name = 'updated_at'
          AND table_schema = 'public'
          AND table_name NOT LIKE '%_archive'
    LOOP
        EXECUTE format(
            'DROP TRIGGER IF EXISTS trg_%I_updated_at ON %I;
             CREATE TRIGGER trg_%I_updated_at
                 BEFORE UPDATE ON %I
                 FOR EACH ROW
                 EXECUTE FUNCTION fn_set_updated_at();',
            tbl, tbl, tbl, tbl
        );
    END LOOP;
END $$;


-- ─────────────────────────────────────────────────────────────────────────────
-- 17b. Full-text search vector maintenance for question_bank
-- ─────────────────────────────────────────────────────────────────────────────

CREATE OR REPLACE FUNCTION fn_question_bank_search_vector()
RETURNS TRIGGER AS $$
BEGIN
    NEW.search_vector :=
        setweight(to_tsvector('english', coalesce(NEW.question_text, '')), 'A') ||
        setweight(to_tsvector('english', coalesce(NEW.answer_text, '')), 'B') ||
        setweight(to_tsvector('english', coalesce(NEW.explanation, '')), 'C') ||
        setweight(to_tsvector('english', coalesce(array_to_string(NEW.tags, ' '), '')), 'B');
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_question_bank_search ON question_bank;
CREATE TRIGGER trg_question_bank_search
    BEFORE INSERT OR UPDATE OF question_text, answer_text, explanation, tags
    ON question_bank
    FOR EACH ROW
    EXECUTE FUNCTION fn_question_bank_search_vector();


-- ─────────────────────────────────────────────────────────────────────────────
-- 17c. Full-text search vector maintenance for practice_questions
-- ─────────────────────────────────────────────────────────────────────────────

CREATE OR REPLACE FUNCTION fn_practice_q_search_vector()
RETURNS TRIGGER AS $$
BEGIN
    NEW.search_vector :=
        setweight(to_tsvector('english', coalesce(NEW.question_text, '')), 'A') ||
        setweight(to_tsvector('english', coalesce(NEW.answer_text, '')), 'B') ||
        setweight(to_tsvector('english', coalesce(NEW.explanation, '')), 'C') ||
        setweight(to_tsvector('english', coalesce(array_to_string(NEW.tags, ' '), '')), 'B');
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_practice_q_search ON practice_questions;
CREATE TRIGGER trg_practice_q_search
    BEFORE INSERT OR UPDATE OF question_text, answer_text, explanation, tags
    ON practice_questions
    FOR EACH ROW
    EXECUTE FUNCTION fn_practice_q_search_vector();


-- ─────────────────────────────────────────────────────────────────────────────
-- 17d. Question versioning — snapshot row into question_versions on UPDATE
-- ─────────────────────────────────────────────────────────────────────────────

CREATE OR REPLACE FUNCTION fn_question_version_on_update()
RETURNS TRIGGER AS $$
BEGIN
    -- Archive the OLD version before the update overwrites it
    INSERT INTO question_versions (
        question_id, version, question_text, answer_text, explanation,
        type, difficulty, marks, tags, options, changed_by, changed_at
    ) VALUES (
        OLD.id, OLD.version, OLD.question_text, OLD.answer_text, OLD.explanation,
        OLD.type, OLD.difficulty, OLD.marks, OLD.tags, OLD.options,
        NEW.created_by,  -- use new row's created_by as the changer
        now()
    );
    -- Bump version on the live row
    NEW.version := OLD.version + 1;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_question_bank_versioning ON question_bank;
CREATE TRIGGER trg_question_bank_versioning
    BEFORE UPDATE OF question_text, answer_text, explanation, type, difficulty, marks, tags, options
    ON question_bank
    FOR EACH ROW
    EXECUTE FUNCTION fn_question_version_on_update();


-- ─────────────────────────────────────────────────────────────────────────────
-- 17e. Notification enqueue on syllabus / question / practice_question changes
-- ─────────────────────────────────────────────────────────────────────────────

CREATE OR REPLACE FUNCTION fn_enqueue_change_notification()
RETURNS TRIGGER AS $$
DECLARE
    v_subject TEXT;
    v_payload JSONB;
BEGIN
    v_subject := format('%s updated: %s', TG_TABLE_NAME, TG_OP);
    v_payload := jsonb_build_object(
        'table',      TG_TABLE_NAME,
        'action',     TG_OP,
        'record_id',  CASE WHEN TG_OP = 'DELETE' THEN OLD.id ELSE NEW.id END,
        'changed_at', now()
    );

    INSERT INTO notifications_queue (channel, subject, payload, status, run_after)
    VALUES ('in_app', v_subject, v_payload, 'pending', now());

    RETURN COALESCE(NEW, OLD);
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_syllabus_notify ON syllabus;
CREATE TRIGGER trg_syllabus_notify
    AFTER INSERT OR UPDATE OR DELETE ON syllabus
    FOR EACH ROW
    EXECUTE FUNCTION fn_enqueue_change_notification();

DROP TRIGGER IF EXISTS trg_question_bank_notify ON question_bank;
CREATE TRIGGER trg_question_bank_notify
    AFTER INSERT OR UPDATE OR DELETE ON question_bank
    FOR EACH ROW
    EXECUTE FUNCTION fn_enqueue_change_notification();

DROP TRIGGER IF EXISTS trg_practice_q_notify ON practice_questions;
CREATE TRIGGER trg_practice_q_notify
    AFTER INSERT OR UPDATE OR DELETE ON practice_questions
    FOR EACH ROW
    EXECUTE FUNCTION fn_enqueue_change_notification();


-- ─────────────────────────────────────────────────────────────────────────────
-- 17f. Prevent duplicate active enrollments (belt-and-suspenders with partial index)
-- ─────────────────────────────────────────────────────────────────────────────

CREATE OR REPLACE FUNCTION fn_prevent_duplicate_enrollment()
RETURNS TRIGGER AS $$
BEGIN
    IF EXISTS (
        SELECT 1 FROM course_enrollments
        WHERE student_id = NEW.student_id
          AND course_id  = NEW.course_id
          AND status     = 'active'
          AND is_deleted = FALSE
          AND id         != COALESCE(NEW.id, 0)
    ) THEN
        RAISE EXCEPTION 'Student % already has an active enrollment in course %',
            NEW.student_id, NEW.course_id;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_enrollment_no_dup ON course_enrollments;
CREATE TRIGGER trg_enrollment_no_dup
    BEFORE INSERT OR UPDATE ON course_enrollments
    FOR EACH ROW
    WHEN (NEW.status = 'active' AND NEW.is_deleted = FALSE)
    EXECUTE FUNCTION fn_prevent_duplicate_enrollment();


-- ─────────────────────────────────────────────────────────────────────────────
-- 17g. Generic audit-log trigger for critical tables
-- ─────────────────────────────────────────────────────────────────────────────

CREATE OR REPLACE FUNCTION fn_audit_log()
RETURNS TRIGGER AS $$
BEGIN
    IF TG_OP = 'INSERT' THEN
        INSERT INTO audit_logs (table_name, record_id, action, new_data)
        VALUES (TG_TABLE_NAME, NEW.id, 'INSERT', to_jsonb(NEW));
    ELSIF TG_OP = 'UPDATE' THEN
        INSERT INTO audit_logs (table_name, record_id, action, old_data, new_data)
        VALUES (TG_TABLE_NAME, NEW.id, 'UPDATE', to_jsonb(OLD), to_jsonb(NEW));
    ELSIF TG_OP = 'DELETE' THEN
        INSERT INTO audit_logs (table_name, record_id, action, old_data)
        VALUES (TG_TABLE_NAME, OLD.id, 'DELETE', to_jsonb(OLD));
    END IF;
    RETURN COALESCE(NEW, OLD);
END;
$$ LANGUAGE plpgsql;

-- Attach audit triggers to critical tables
DO $$
DECLARE
    tbl TEXT;
BEGIN
    FOR tbl IN
        SELECT unnest(ARRAY[
            'users', 'courses', 'course_enrollments', 'question_bank',
            'practice_questions', 'student_records', 'syllabus', 'settings'
        ])
    LOOP
        EXECUTE format(
            'DROP TRIGGER IF EXISTS trg_%I_audit ON %I;
             CREATE TRIGGER trg_%I_audit
                 AFTER INSERT OR UPDATE OR DELETE ON %I
                 FOR EACH ROW
                 EXECUTE FUNCTION fn_audit_log();',
            tbl, tbl, tbl, tbl
        );
    END LOOP;
END $$;


-- =============================================================================
-- 18. VIEWS
-- =============================================================================

-- ─────────────────────────────────────────────────────────────────────────────
-- 18a. Student Dashboard View
-- ─────────────────────────────────────────────────────────────────────────────

CREATE OR REPLACE VIEW student_dashboard_view AS
SELECT
    u.id                AS user_id,
    u.name              AS student_name,
    u.email,
    u.status            AS account_status,
    sp.enrollment_no,
    sp.dob,
    sp.gender,
    sp.mobile,
    sp.photo,
    b.name              AS branch_name,
    b.code              AS branch_code,
    sem.number          AS semester_number,
    sem.label           AS semester_label,
    sa.current_cgpa,
    sa.backlogs,
    -- Aggregates
    (SELECT COUNT(*) FROM course_enrollments ce
        WHERE ce.student_id = u.id AND ce.status = 'active' AND ce.is_deleted = FALSE
    ) AS active_courses,
    (SELECT COUNT(*) FROM student_attempts sa2
        WHERE sa2.student_id = u.id
    ) AS total_attempts,
    (SELECT COUNT(*) FROM queries q
        WHERE q.student_id = u.id AND q.status = 'open' AND q.is_deleted = FALSE
    ) AS open_queries,
    (SELECT COUNT(*) FROM notifications_queue nq
        WHERE nq.recipient_id = u.id AND nq.status = 'pending'
    ) AS pending_notifications
FROM users u
LEFT JOIN student_profiles sp ON sp.user_id = u.id
LEFT JOIN student_academics sa ON sa.user_id = u.id
LEFT JOIN branches b          ON b.id = sp.branch_id
LEFT JOIN semesters sem       ON sem.id = sp.semester_id
WHERE u.role = 'student'
  AND u.is_deleted = FALSE;


-- ─────────────────────────────────────────────────────────────────────────────
-- 18b. Staff Dashboard View
-- ─────────────────────────────────────────────────────────────────────────────

CREATE OR REPLACE VIEW staff_dashboard_view AS
SELECT
    u.id                AS user_id,
    u.name              AS staff_name,
    u.email,
    u.status            AS account_status,
    sfp.employee_id,
    sfp.department,
    sfp.designation,
    sfp.qualification,
    sfp.experience_years,
    sfp.photo,
    -- Aggregates
    (SELECT COUNT(*) FROM courses c
        WHERE c.staff_id = u.id AND c.is_deleted = FALSE
    ) AS assigned_courses,
    (SELECT COUNT(*) FROM question_bank qb
        WHERE qb.created_by = u.id AND qb.is_deleted = FALSE
    ) AS questions_created,
    (SELECT COUNT(*) FROM queries q
        WHERE q.assigned_to = u.id AND q.status IN ('open', 'in_progress') AND q.is_deleted = FALSE
    ) AS pending_queries,
    (SELECT COUNT(*) FROM study_materials sm
        WHERE sm.uploaded_by = u.id AND sm.is_deleted = FALSE
    ) AS materials_uploaded
FROM users u
LEFT JOIN staff_profiles sfp ON sfp.user_id = u.id
WHERE u.role = 'staff'
  AND u.is_deleted = FALSE;


-- =============================================================================
-- 19. HELPER ROUTINES
-- =============================================================================

-- ─────────────────────────────────────────────────────────────────────────────
-- 19a. Paginated practice questions with filtering
-- ─────────────────────────────────────────────────────────────────────────────

CREATE OR REPLACE FUNCTION fn_get_practice_questions(
    p_course_id     BIGINT          DEFAULT NULL,
    p_tags          TEXT[]          DEFAULT NULL,
    p_difficulty    difficulty_level DEFAULT NULL,
    p_search_term   TEXT            DEFAULT NULL,
    p_page          INTEGER         DEFAULT 1,
    p_page_size     INTEGER         DEFAULT 20
)
RETURNS TABLE (
    id              BIGINT,
    course_id       BIGINT,
    course_name     VARCHAR(200),
    question_text   TEXT,
    answer_text     TEXT,
    explanation     TEXT,
    type            question_type,
    difficulty      difficulty_level,
    tags            TEXT[],
    source          VARCHAR(200),
    year            SMALLINT,
    marks           SMALLINT,
    created_at      TIMESTAMPTZ,
    total_count     BIGINT
) AS $$
DECLARE
    v_offset INTEGER;
    v_tsquery tsquery;
BEGIN
    v_offset := (GREATEST(p_page, 1) - 1) * p_page_size;

    IF p_search_term IS NOT NULL AND p_search_term <> '' THEN
        v_tsquery := plainto_tsquery('english', p_search_term);
    END IF;

    RETURN QUERY
    SELECT
        pq.id,
        pq.course_id,
        c.name                  AS course_name,
        pq.question_text,
        pq.answer_text,
        pq.explanation,
        pq.type,
        pq.difficulty,
        pq.tags,
        pq.source,
        pq.year,
        pq.marks,
        pq.created_at,
        COUNT(*) OVER()         AS total_count
    FROM practice_questions pq
    JOIN courses c ON c.id = pq.course_id
    WHERE pq.is_deleted = FALSE
      AND (p_course_id   IS NULL OR pq.course_id  = p_course_id)
      AND (p_difficulty   IS NULL OR pq.difficulty  = p_difficulty)
      AND (p_tags         IS NULL OR pq.tags        && p_tags)         -- array overlap
      AND (v_tsquery      IS NULL OR pq.search_vector @@ v_tsquery)
    ORDER BY pq.created_at DESC
    LIMIT p_page_size
    OFFSET v_offset;
END;
$$ LANGUAGE plpgsql STABLE;

COMMENT ON FUNCTION fn_get_practice_questions IS
    'Returns paginated practice questions filtered by course, tags, difficulty, and full-text search. total_count column gives the unwindowed count for pagination UI.';


-- =============================================================================
-- 20. SEED DATA
-- =============================================================================

-- ─── Branches ─────────────────────────────────────────────────────────────────

INSERT INTO branches (name, code) VALUES
    ('Information Technology', 'IT'),
    ('Computer Science & Engineering', 'CSE'),
    ('Electronics & Telecommunication', 'ENTC')
ON CONFLICT (code) DO NOTHING;

-- ─── Semesters ────────────────────────────────────────────────────────────────

INSERT INTO semesters (number, label) VALUES
    (1, 'Semester 1'), (2, 'Semester 2'), (3, 'Semester 3'),
    (4, 'Semester 4'), (5, 'Semester 5'), (6, 'Semester 6'),
    (7, 'Semester 7'), (8, 'Semester 8')
ON CONFLICT (number) DO NOTHING;

-- ─── Roles ────────────────────────────────────────────────────────────────────

INSERT INTO roles (name, description, is_system) VALUES
    ('admin',   'Full system administrator',   TRUE),
    ('staff',   'Teaching staff / faculty',     TRUE),
    ('student', 'Enrolled student',             TRUE)
ON CONFLICT (name) DO NOTHING;

-- ─── Role Permissions ─────────────────────────────────────────────────────────

INSERT INTO role_permissions (role_id, permission)
SELECT r.id, p.perm
FROM roles r
CROSS JOIN (VALUES
    ('course.create'), ('course.update'), ('course.delete'),
    ('question.create'), ('question.update'), ('question.delete'),
    ('material.upload'), ('material.delete'),
    ('user.manage'), ('settings.manage')
) AS p(perm)
WHERE r.name = 'admin'
ON CONFLICT (role_id, permission) DO NOTHING;

INSERT INTO role_permissions (role_id, permission)
SELECT r.id, p.perm
FROM roles r
CROSS JOIN (VALUES
    ('course.update'),
    ('question.create'), ('question.update'),
    ('material.upload'),
    ('query.reply')
) AS p(perm)
WHERE r.name = 'staff'
ON CONFLICT (role_id, permission) DO NOTHING;

INSERT INTO role_permissions (role_id, permission)
SELECT r.id, p.perm
FROM roles r
CROSS JOIN (VALUES
    ('course.view'),
    ('question.view'),
    ('material.download'),
    ('query.create'), ('query.view'),
    ('attempt.submit')
) AS p(perm)
WHERE r.name = 'student'
ON CONFLICT (role_id, permission) DO NOTHING;

-- ─── Users (3: 2 students + 1 staff) ─────────────────────────────────────────
-- password_hash = bcrypt('Password@123') — example only; app must hash properly

INSERT INTO users (email, name, password_hash, role, status) VALUES
    ('rahul.sharma@zeal.edu.in',   'Rahul Sharma',   '$2a$12$LJ3m5ZQnLmMmR0rKvRqZeOGWCIk5k6FLEqGJ8BkqZ3Z6a7sFh0cWS', 'student', 'active'),
    ('priya.patil@zeal.edu.in',    'Priya Patil',    '$2a$12$LJ3m5ZQnLmMmR0rKvRqZeOGWCIk5k6FLEqGJ8BkqZ3Z6a7sFh0cWS', 'student', 'active'),
    ('prof.deshmukh@zeal.edu.in',  'Prof. Deshmukh', '$2a$12$LJ3m5ZQnLmMmR0rKvRqZeOGWCIk5k6FLEqGJ8BkqZ3Z6a7sFh0cWS', 'staff',   'active')
ON CONFLICT (email) DO NOTHING;

-- ─── Student Profiles ─────────────────────────────────────────────────────────

INSERT INTO student_profiles (user_id, branch_id, semester_id, enrollment_no, dob, gender, mobile, city, state, pincode, father_name)
SELECT u.id,
       (SELECT id FROM branches WHERE code = 'IT'),
       (SELECT id FROM semesters WHERE number = 5),
       'ZCE-IT-2023-001',
       '2003-06-15'::DATE,
       'male'::gender_type,
       '9876543210',
       'Pune', 'Maharashtra', '411001',
       'Suresh Sharma'
FROM users u WHERE u.email = 'rahul.sharma@zeal.edu.in'
ON CONFLICT (user_id) DO NOTHING;

INSERT INTO student_profiles (user_id, branch_id, semester_id, enrollment_no, dob, gender, mobile, city, state, pincode, father_name)
SELECT u.id,
       (SELECT id FROM branches WHERE code = 'IT'),
       (SELECT id FROM semesters WHERE number = 5),
       'ZCE-IT-2023-002',
       '2003-09-22'::DATE,
       'female'::gender_type,
       '9123456780',
       'Pune', 'Maharashtra', '411038',
       'Rajesh Patil'
FROM users u WHERE u.email = 'priya.patil@zeal.edu.in'
ON CONFLICT (user_id) DO NOTHING;

-- ─── Student Academics ────────────────────────────────────────────────────────

INSERT INTO student_academics (user_id, ssc_percentage, hsc_percentage, current_cgpa, backlogs, admission_year)
SELECT u.id, 89.60, 82.40, 8.45, 0, 2023
FROM users u WHERE u.email = 'rahul.sharma@zeal.edu.in'
ON CONFLICT (user_id) DO NOTHING;

INSERT INTO student_academics (user_id, ssc_percentage, hsc_percentage, current_cgpa, backlogs, admission_year)
SELECT u.id, 92.00, 88.80, 9.10, 0, 2023
FROM users u WHERE u.email = 'priya.patil@zeal.edu.in'
ON CONFLICT (user_id) DO NOTHING;

-- ─── Staff Profile ────────────────────────────────────────────────────────────

INSERT INTO staff_profiles (user_id, employee_id, department, designation, qualification, specialization, experience_years)
SELECT u.id, 'ZCE-STAFF-101', 'Information Technology', 'Assistant Professor',
       'M.Tech Computer Science', 'Database Systems & Web Technologies', 12
FROM users u WHERE u.email = 'prof.deshmukh@zeal.edu.in'
ON CONFLICT (user_id) DO NOTHING;

-- ─── Compilers ────────────────────────────────────────────────────────────────

INSERT INTO compilers (name, language_key, version) VALUES
    ('C',       'c',        'GCC 12'),
    ('C++',     'cpp',      'G++ 12'),
    ('Java',    'java',     'OpenJDK 17'),
    ('Python',  'python3',  'CPython 3.11'),
    ('PHP',     'php',      'PHP 8.2'),
    ('SQL',     'sql',      'PostgreSQL 14')
ON CONFLICT (language_key) DO NOTHING;

-- ─── Course + Syllabus + Labs ─────────────────────────────────────────────────

INSERT INTO courses (code, name, description, branch_id, semester_id, credits, staff_id)
SELECT
    'IT501',
    'Database Management Systems',
    'Comprehensive course covering relational algebra, SQL, normalization, transactions, indexing, and NoSQL fundamentals.',
    (SELECT id FROM branches WHERE code = 'IT'),
    (SELECT id FROM semesters WHERE number = 5),
    4,
    (SELECT id FROM users WHERE email = 'prof.deshmukh@zeal.edu.in')
ON CONFLICT (code) DO NOTHING;

-- Syllabus entries
INSERT INTO syllabus (course_id, title, unit_number, description, topics, uploaded_by)
SELECT c.id,
       'Introduction to DBMS',
       1,
       'Fundamentals of database systems, data models, and architecture.',
       'Database concepts; Data models; Three-schema architecture; Data independence; DBMS architecture',
       (SELECT id FROM users WHERE email = 'prof.deshmukh@zeal.edu.in')
FROM courses c WHERE c.code = 'IT501';

INSERT INTO syllabus (course_id, title, unit_number, description, topics, uploaded_by)
SELECT c.id,
       'Relational Model & SQL',
       2,
       'Relational algebra, calculus, and Structured Query Language.',
       'Relational algebra; Tuple calculus; SQL DDL; SQL DML; Joins; Subqueries; Views; Stored procedures',
       (SELECT id FROM users WHERE email = 'prof.deshmukh@zeal.edu.in')
FROM courses c WHERE c.code = 'IT501';

-- Lab entries
INSERT INTO labs (course_id, title, description, instructions, compiler_id, starter_code, expected_output, difficulty, sort_order, uploaded_by)
SELECT c.id,
       'Lab 1: Basic SQL Queries',
       'Practice SELECT, INSERT, UPDATE, DELETE statements.',
       'Create a sample "employees" table and write queries for the exercises below.',
       (SELECT id FROM compilers WHERE language_key = 'sql'),
       E'-- Create the employees table\nCREATE TABLE employees (\n    id SERIAL PRIMARY KEY,\n    name VARCHAR(100),\n    department VARCHAR(50),\n    salary NUMERIC(10,2)\n);\n\n-- Insert sample data\nINSERT INTO employees (name, department, salary) VALUES\n(''Alice'', ''IT'', 75000),\n(''Bob'', ''HR'', 62000),\n(''Charlie'', ''IT'', 80000);\n\n-- Exercise: Write a SELECT to find IT employees earning > 70000',
       E'Alice | IT | 75000\nCharlie | IT | 80000',
       'easy',
       1,
       (SELECT id FROM users WHERE email = 'prof.deshmukh@zeal.edu.in')
FROM courses c WHERE c.code = 'IT501';

INSERT INTO labs (course_id, title, description, instructions, compiler_id, difficulty, sort_order, uploaded_by)
SELECT c.id,
       'Lab 2: Joins and Subqueries',
       'Practice INNER JOIN, LEFT JOIN, RIGHT JOIN, and correlated subqueries.',
       'Use the provided schema to write join queries across multiple tables.',
       (SELECT id FROM compilers WHERE language_key = 'sql'),
       'medium',
       2,
       (SELECT id FROM users WHERE email = 'prof.deshmukh@zeal.edu.in')
FROM courses c WHERE c.code = 'IT501';

-- ─── Course Enrollments ───────────────────────────────────────────────────────

INSERT INTO course_enrollments (student_id, course_id, status)
SELECT u.id, c.id, 'active'
FROM users u, courses c
WHERE u.email = 'rahul.sharma@zeal.edu.in' AND c.code = 'IT501'
ON CONFLICT DO NOTHING;

INSERT INTO course_enrollments (student_id, course_id, status)
SELECT u.id, c.id, 'active'
FROM users u, courses c
WHERE u.email = 'priya.patil@zeal.edu.in' AND c.code = 'IT501'
ON CONFLICT DO NOTHING;

-- ─── Question Bank (5 rows) ──────────────────────────────────────────────────

INSERT INTO question_bank (course_id, question_text, answer_text, explanation, type, difficulty, marks, tags, options, created_by)
SELECT c.id,
       'What is normalization in DBMS? Explain 1NF, 2NF, and 3NF with examples.',
       'Normalization is the process of organizing data to reduce redundancy. 1NF eliminates repeating groups, 2NF removes partial dependencies, 3NF removes transitive dependencies.',
       'Key concept in relational database design to minimize data anomalies.',
       'long_answer', 'medium', 10,
       ARRAY['DBMS', 'Normalization', 'Design'],
       NULL,
       (SELECT id FROM users WHERE email = 'prof.deshmukh@zeal.edu.in')
FROM courses c WHERE c.code = 'IT501';

INSERT INTO question_bank (course_id, question_text, answer_text, type, difficulty, marks, tags, options, created_by)
SELECT c.id,
       'Which of the following is NOT a valid SQL aggregate function?',
       'B',
       'mcq', 'easy', 2,
       ARRAY['SQL', 'Aggregate', 'MCQ'],
       '[{"key":"A","text":"COUNT()","is_correct":false},{"key":"B","text":"TOTAL()","is_correct":true},{"key":"C","text":"AVG()","is_correct":false},{"key":"D","text":"SUM()","is_correct":false}]'::JSONB,
       (SELECT id FROM users WHERE email = 'prof.deshmukh@zeal.edu.in')
FROM courses c WHERE c.code = 'IT501';

INSERT INTO question_bank (course_id, question_text, answer_text, type, difficulty, marks, tags, options, created_by)
SELECT c.id,
       'ACID stands for Atomicity, Consistency, Isolation, and Durability.',
       'True',
       'true_false', 'easy', 1,
       ARRAY['DBMS', 'Transactions', 'ACID'],
       '[{"key":"A","text":"True","is_correct":true},{"key":"B","text":"False","is_correct":false}]'::JSONB,
       (SELECT id FROM users WHERE email = 'prof.deshmukh@zeal.edu.in')
FROM courses c WHERE c.code = 'IT501';

INSERT INTO question_bank (course_id, question_text, answer_text, explanation, type, difficulty, marks, tags, created_by)
SELECT c.id,
       'Explain the difference between INNER JOIN and LEFT JOIN with a practical example.',
       'INNER JOIN returns only matching rows from both tables. LEFT JOIN returns all rows from the left table and matched rows from the right table, with NULLs for non-matching right rows.',
       'Understanding join types is essential for writing efficient queries.',
       'long_answer', 'medium', 8,
       ARRAY['SQL', 'Joins', 'Query'],
       (SELECT id FROM users WHERE email = 'prof.deshmukh@zeal.edu.in')
FROM courses c WHERE c.code = 'IT501';

INSERT INTO question_bank (course_id, question_text, answer_text, explanation, type, difficulty, marks, tags, options, created_by)
SELECT c.id,
       'What is the purpose of a B+ Tree index in a database system? Describe its structure and advantages over a hash index for range queries.',
       'B+ Tree stores keys in sorted order in internal nodes and data pointers in leaf nodes which are linked. This allows efficient range queries, unlike hash indexes which only support equality lookups.',
       'Indexing is a core performance optimization topic.',
       'long_answer', 'hard', 12,
       ARRAY['DBMS', 'Indexing', 'B+Tree', 'Performance'],
       NULL,
       (SELECT id FROM users WHERE email = 'prof.deshmukh@zeal.edu.in')
FROM courses c WHERE c.code = 'IT501';


-- ─── Practice Questions (10 rows) ────────────────────────────────────────────

INSERT INTO practice_questions (course_id, question_text, answer_text, type, difficulty, tags, source, year, marks, created_by)
SELECT c.id, q.question_text, q.answer_text, q.type::question_type, q.difficulty::difficulty_level,
       q.tags::TEXT[], q.source, q.year, q.marks,
       (SELECT id FROM users WHERE email = 'prof.deshmukh@zeal.edu.in')
FROM courses c,
(VALUES
    ('Define a primary key and give an example.',
     'A primary key uniquely identifies each row in a table. Example: student_id in a students table.',
     'short_answer', 'easy', '{DBMS,Keys,Basics}', 'Model Paper', 2025::SMALLINT, 4),

    ('Write an SQL query to find the second highest salary from an employee table.',
     'SELECT MAX(salary) FROM employees WHERE salary < (SELECT MAX(salary) FROM employees);',
     'coding', 'medium', '{SQL,Subquery,Interview}', 'Previous Year 2024', 2024::SMALLINT, 5),

    ('Differentiate between DELETE, TRUNCATE, and DROP commands.',
     'DELETE removes rows with WHERE, TRUNCATE removes all rows resetting identity, DROP removes the table entirely.',
     'short_answer', 'easy', '{SQL,DDL,DML}', 'Model Paper', 2025::SMALLINT, 4),

    ('What is a deadlock? How can it be prevented?',
     'A deadlock occurs when two or more transactions wait for each other to release locks. Prevention methods include lock ordering, timeouts, and deadlock detection algorithms.',
     'long_answer', 'hard', '{DBMS,Transactions,Concurrency}', 'Previous Year 2024', 2024::SMALLINT, 8),

    ('List the properties of a relation in the relational model.',
     'Each row is unique, column values are atomic, column order is insignificant, row order is insignificant, each column has a unique name.',
     'short_answer', 'easy', '{DBMS,Relational Model}', 'Model Paper', 2025::SMALLINT, 3),

    ('Explain the concept of a view in SQL. Can you update data through a view?',
     'A view is a virtual table based on a SELECT query. Simple views (single table, no aggregates) are updatable; complex views generally are not.',
     'long_answer', 'medium', '{SQL,Views}', 'Previous Year 2023', 2023::SMALLINT, 6),

    ('Write a PL/SQL block to print the factorial of a number.',
     'DECLARE n NUMBER := 5; fact NUMBER := 1; BEGIN FOR i IN 1..n LOOP fact := fact * i; END LOOP; DBMS_OUTPUT.PUT_LINE(fact); END;',
     'coding', 'medium', '{PL/SQL,Procedure,Loop}', 'Lab Exam 2024', 2024::SMALLINT, 5),

    ('What is a transaction log? Why is it important for recovery?',
     'A transaction log records all changes made by transactions. It is critical for crash recovery (UNDO/REDO) to ensure database consistency after failures.',
     'short_answer', 'medium', '{DBMS,Recovery,Logging}', 'Model Paper', 2025::SMALLINT, 4),

    ('Explain the three-schema architecture of a DBMS.',
     'External schema (user views), Conceptual schema (logical structure), Internal schema (physical storage). Provides data independence between levels.',
     'long_answer', 'medium', '{DBMS,Architecture}', 'Previous Year 2024', 2024::SMALLINT, 8),

    ('What are the CODD rules? List any 6 of the 12 rules.',
     'Rule 0: Foundation Rule, Rule 1: Information Rule, Rule 2: Guaranteed Access, Rule 3: Systematic Treatment of NULLs, Rule 4: Active Online Catalog, Rule 5: Comprehensive Data Sublanguage.',
     'long_answer', 'hard', '{DBMS,Codd Rules,Theory}', 'Previous Year 2023', 2023::SMALLINT, 10)
) AS q(question_text, answer_text, type, difficulty, tags, source, year, marks)
WHERE c.code = 'IT501';


-- ─── Student Records (3 rows) ────────────────────────────────────────────────

INSERT INTO student_records (student_id, course_id, exam_type, marks_obtained, marks_total, grade, semester_id, exam_date)
SELECT u.id, c.id, 'internal', 38.00, 50.00, 'A', sem.id, '2026-03-15'::DATE
FROM users u, courses c, semesters sem
WHERE u.email = 'rahul.sharma@zeal.edu.in' AND c.code = 'IT501' AND sem.number = 5;

INSERT INTO student_records (student_id, course_id, exam_type, marks_obtained, marks_total, grade, semester_id, exam_date)
SELECT u.id, c.id, 'internal', 44.00, 50.00, 'A+', sem.id, '2026-03-15'::DATE
FROM users u, courses c, semesters sem
WHERE u.email = 'priya.patil@zeal.edu.in' AND c.code = 'IT501' AND sem.number = 5;

INSERT INTO student_records (student_id, course_id, exam_type, marks_obtained, marks_total, grade, semester_id, exam_date)
SELECT u.id, c.id, 'lab', 22.00, 25.00, 'A', sem.id, '2026-04-10'::DATE
FROM users u, courses c, semesters sem
WHERE u.email = 'rahul.sharma@zeal.edu.in' AND c.code = 'IT501' AND sem.number = 5;


-- ─── Settings ─────────────────────────────────────────────────────────────────

INSERT INTO settings (key, value, description, is_public) VALUES
    ('portal.name',            'Study Material Sharing and Practice Portal', 'Public-facing portal name', TRUE),
    ('portal.department',      'Department of Information Technology',       'Displayed in footer',       TRUE),
    ('portal.college',         'Zeal College of Engineering and Research',   'College name',              TRUE),
    ('notifications.enabled',  'true',                                       'Global notification toggle', FALSE),
    ('upload.max_size_mb',     '25',                                         'Maximum upload size in MB',  FALSE)
ON CONFLICT (key) DO NOTHING;


-- ─── 1 Pending Notification ──────────────────────────────────────────────────

INSERT INTO notifications_queue (channel, recipient_id, subject, payload, status, run_after)
SELECT 'in_app',
       (SELECT id FROM users WHERE email = 'rahul.sharma@zeal.edu.in'),
       'New syllabus uploaded for DBMS',
       jsonb_build_object(
           'type', 'syllabus_update',
           'course_code', 'IT501',
           'message', 'Unit 2: Relational Model & SQL has been uploaded by Prof. Deshmukh.'
       ),
       'pending',
       now();


-- =============================================================================
-- 21. EXAMPLE: SAFE NOTIFICATION WORKER DEQUEUE (SELECT FOR UPDATE SKIP LOCKED)
-- =============================================================================
-- This is a template for application-layer worker code.  Run inside a transaction.
--
--   BEGIN;
--
--   -- Claim up to 10 pending notifications that are ready to run
--   WITH claimed AS (
--       SELECT id
--       FROM notifications_queue
--       WHERE status = 'pending'
--         AND run_after <= now()
--         AND attempts < max_attempts
--       ORDER BY run_after ASC
--       LIMIT 10
--       FOR UPDATE SKIP LOCKED
--   )
--   UPDATE notifications_queue nq
--   SET    status          = 'in_flight',
--          attempts        = attempts + 1,
--          last_attempt_at = now(),
--          updated_at      = now()
--   FROM   claimed
--   WHERE  nq.id = claimed.id
--   RETURNING nq.*;
--
--   -- Application processes each returned row (send email / push / in-app)
--   -- On success:
--   --   UPDATE notifications_queue SET status = 'sent', sent_at = now() WHERE id = <id>;
--   -- On failure:
--   --   UPDATE notifications_queue
--   --   SET    status = CASE WHEN attempts >= max_attempts THEN 'failed' ELSE 'pending' END,
--   --          error_message = '<error details>',
--   --          run_after = now() + interval '30 seconds' * attempts  -- exponential-ish backoff
--   --   WHERE  id = <id>;
--
--   COMMIT;
-- =============================================================================


COMMIT;

-- =============================================================================
-- END OF FILE
-- =============================================================================
