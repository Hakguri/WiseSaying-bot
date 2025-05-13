import mysql.connector
from datetime import datetime
import os
from dotenv import load_dotenv
from pathlib import Path

# .env 파일 로드
dotenv_path = Path(__file__).resolve().parent.parent / '.env'
load_dotenv(dotenv_path)

# MySQL 연결 설정
db_config = {
    'host': os.getenv('DB_HOST'),
    'user': os.getenv('DB_USER'),
    'password': os.getenv('DB_PASSWORD'),
    'database': os.getenv('DB_NAME'),
    'port': int(os.getenv('DB_PORT', 3306))
}

def get_db_connection():
    try:
        return mysql.connector.connect(**db_config)
    except mysql.connector.Error as err:
        print(f"데이터베이스 연결 오류: {err}")
        raise

def save_quote_to_db(quote_text, author, source='gemini'):
    """명언을 데이터베이스에 저장"""
    conn = get_db_connection()
    cursor = conn.cursor()
    
    try:
        # 명언을 게시물로 저장
        sql = """
        INSERT INTO posts (title, author, content, is_quote, source, quote_date)
        VALUES (%s, %s, %s, TRUE, %s, %s)
        """
        cursor.execute(sql, (
            f"{author}의 투자 명언",
            author,
            quote_text,
            source,
            datetime.now().date()
        ))
        
        post_id = cursor.lastrowid
        conn.commit()
        return post_id
    except Exception as e:
        print(f"Error saving quote: {e}")
        conn.rollback()
        return None
    finally:
        cursor.close()
        conn.close()

def get_todays_quote():
    """오늘의 명언 가져오기"""
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    
    try:
        sql = """
        SELECT id, title, author, content, source
        FROM posts
        WHERE is_quote = TRUE AND quote_date = CURDATE()
        ORDER BY id DESC
        LIMIT 1
        """
        cursor.execute(sql)
        return cursor.fetchone()
    finally:
        cursor.close()
        conn.close()

def save_quote_to_bbs(quote_text, author, date_str):
    conn = get_db_connection()
    cursor = conn.cursor()
    try:
        sql = """
        INSERT INTO posts (title, author, content)
        VALUES (%s, %s, %s)
        """
        title = f"{date_str}의 명언"
        cursor.execute(sql, (title, author, quote_text))
        post_id = cursor.lastrowid
        conn.commit()
        return post_id
    except Exception as e:
        print(f"BBS 저장 오류: {e}")
        conn.rollback()
        return None
    finally:
        cursor.close()
        conn.close()

if __name__ == "__main__":
    # 테스트용 임의 명언
    test_quote = "성공은 준비된 자에게 온다."
    test_author = "테스트 저자"
    test_source = "test"
    post_id = save_quote_to_db(test_quote, test_author, test_source)
    if post_id:
        print(f"테스트 데이터 저장 성공! post_id: {post_id}")
    else:
        print("테스트 데이터 저장 실패")