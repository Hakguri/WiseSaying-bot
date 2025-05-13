-- posts 테이블에 명언 관련 필드 추가
ALTER TABLE posts 
ADD COLUMN source VARCHAR(50) DEFAULT 'manual' COMMENT '명언 출처 (gemini, manual)',
ADD COLUMN is_quote BOOLEAN DEFAULT FALSE COMMENT '명언 여부',
ADD COLUMN quote_date DATE DEFAULT NULL COMMENT '명언 생성일';

-- 명언 테이블 인덱스 추가
CREATE INDEX idx_quotes ON posts (is_quote, quote_date);

-- 기존 게시물은 일반 게시물로 설정
UPDATE posts SET is_quote = FALSE WHERE is_quote IS NULL;

-- 테스트용 명언 데이터 삽입
INSERT INTO posts (title, author, content, is_quote, source, quote_date) VALUES
('투자의 기본은 인내심이다', '워렌 버핏', '시장이 단기적으로는 투표 기계처럼 작동하지만, 장기적으로는 저울처럼 작동합니다.', TRUE, 'manual', CURDATE()),
('위험은 당신이 무엇을 하는지 모를 때 발생한다', '피터 린치', '당신이 투자하는 회사에 대해 충분히 이해하지 못했다면, 그것이 바로 가장 큰 위험입니다.', TRUE, 'manual', CURDATE()); 