CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    parent_id INT DEFAULT NULL,  -- 대댓글인 경우 부모 댓글의 ID
    author VARCHAR(50) NOT NULL,
    content TEXT NOT NULL,
    depth INT DEFAULT 0,  -- 댓글 깊이 (0: 일반 댓글, 1: 대댓글)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_deleted TINYINT(1) DEFAULT 0,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE,
    INDEX idx_post_comments (post_id, is_deleted, created_at),
    INDEX idx_parent_comments (parent_id, is_deleted, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 예시 데이터 삽입
INSERT INTO comments (post_id, parent_id, author, content, depth) VALUES
-- 첫 번째 게시물의 댓글들
(1, NULL, '홍길동', '좋은 게시물이네요!', 0),
(1, NULL, '이순신', '매우 유익한 내용입니다.', 0),
-- 홍길동의 댓글에 대한 대댓글들
(1, 1, '김철수', '네, 좋은 의견 감사합니다!', 1),
(1, 1, '이영희', '저도 같은 생각입니다.', 1),
-- 이순신의 댓글에 대한 대댓글
(1, 2, '박지성', '동의합니다!', 1); 