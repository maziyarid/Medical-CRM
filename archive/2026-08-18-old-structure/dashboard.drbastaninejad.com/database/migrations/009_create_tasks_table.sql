-- Migration 009: tasks table
-- dashboard.drbastaninejad.com
-- Clinic staff task Kanban board.

CREATE TABLE IF NOT EXISTS tasks (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id    INT UNSIGNED NOT NULL,
    title        VARCHAR(255) NOT NULL,
    priority     ENUM('high','medium','low') NOT NULL DEFAULT 'medium',
    status       ENUM('todo','in_progress','done') NOT NULL DEFAULT 'todo',
    assignee_id  INT UNSIGNED NULL,
    due_date     DATE         NULL,
    notes        TEXT         NULL,
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tasks_clinic   (clinic_id),
    KEY idx_tasks_status   (status),
    KEY idx_tasks_assignee (assignee_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
