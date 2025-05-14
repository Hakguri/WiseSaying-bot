import sys
from pathlib import Path
sys.path.append(str(Path(__file__).resolve().parent.parent))

import os
from apscheduler.schedulers.blocking import BlockingScheduler
from dotenv import load_dotenv
import requests
from datetime import datetime
from utils import get_random_quote
from gemini.generate_quote import generate_investment_quote
from bot.db import save_quote_to_bbs

# 환경변수 로드
dotenv_path = Path(__file__).resolve().parent.parent / '.env'
load_dotenv(dotenv_path)

TOKEN = os.getenv("TELEGRAM_TOKEN")
CHAT_ID = int(os.getenv("TELEGRAM_CHAT_ID"))
BBS_URL = os.getenv("BBS_URL", "https://wisesaying-bbs.onrender.com")


def send_daily_quote():
    now = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    date_str = datetime.now().strftime("%Y-%m-%d")

    # 1. 명언 생성
    try:
        quote = generate_investment_quote()
        author = "AI 투자 전문가"
    except Exception as e:
        print(f"Gemini API 오류: {e}")
        quote = get_random_quote()
        author = "투자 대가"

    # 2. DB 저장 및 텔레그램 전송
    try:
        post_id = save_quote_to_bbs(quote, author, date_str)
        if not post_id:
            raise Exception("명언 저장 실패")
        post_url = f"{BBS_URL}/view.php?id={post_id}"

        # 텔레그램 메시지 전송
        message = f"📢 {now}\n오늘의 투자 명언입니다:\n\n{quote}\n\n💬 댓글 작성하기: {post_url}"
        url = f"https://api.telegram.org/bot{TOKEN}/sendMessage"
        data = {
            "chat_id": CHAT_ID,
            "text": message,
            "parse_mode": "HTML"
        }
        response = requests.post(url, data=data)
        print(f"[{now}] 메시지 전송 완료: {response.status_code}")

    except Exception as e:
        print(f"[{now}] 오류 발생: {e}")

if __name__ == "__main__":
    scheduler = BlockingScheduler()

    # 실제 배포 시: 매일 오전 8시 실행
    # scheduler.add_job(send_daily_quote, 'cron', hour=8, minute=0)

    # 💡 테스트용 (5분 간격 전송)
    scheduler.add_job(send_daily_quote, 'interval', minutes=1)

    print("⏰ 스케줄러 실행 중... (Ctrl+C로 종료)")
    scheduler.start()