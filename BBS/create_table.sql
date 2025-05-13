-- MySQL 사용자 생성 및 권한 부여
CREATE USER IF NOT EXISTS 'lam2025'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON bbs.* TO 'lam2025'@'localhost';
FLUSH PRIVILEGES;

-- 데이터베이스 생성
CREATE DATABASE IF NOT EXISTS bbs
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_unicode_ci;

USE bbs;

-- 게시판 테이블 생성
CREATE TABLE IF NOT EXISTS posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(50) NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    views INT DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_deleted TINYINT(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 테스트용 데이터 삽입
INSERT INTO posts (title, author, content, views) VALUES
    ('첫 번째 게시물입니다.', '홍길동', '첫 번째 게시물의 내용입니다.', 123),
    ('두 번째 게시물입니다.', '김철수', '두 번째 게시물의 내용입니다.', 45); 