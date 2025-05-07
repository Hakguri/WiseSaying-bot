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
from bot.db import save_quote_to_db

# 환경변수 로드
from pathlib import Path
dotenv_path = Path(__file__).resolve().parent.parent / '.env'
load_dotenv(dotenv_path)

TOKEN = os.getenv("TELEGRAM_TOKEN")
CHAT_ID = int(os.getenv("TELEGRAM_CHAT_ID"))

def send_daily_quote():
  now = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
  try:
    quote = generate_investment_quote()
    source = "gemini"
  except Exception:
    quote = get_random_quote()
    source = "quotes.txt"

  message = f"📢 {now}\n오늘의 투자 명언입니다:\n\n“{quote}”"
  url = f"https://api.telegram.org/bot{TOKEN}/sendMessage"
  data = {
    "chat_id": CHAT_ID,
    "text": message
  }
  response = requests.post(url, data=data)
  print(f"[{now}] 메시지 전송 완료: {response.status_code}")

  # 명언 저장
  save_quote_to_db(quote, source)

if __name__ == "__main__":
  scheduler = BlockingScheduler()

  # 실제 배포 시: 매일 오전 8시 실행
  # scheduler.add_job(send_daily_quote, 'cron', hour=8, minute=0)

  # 💡 테스트용 (1분 간격 전송)
  scheduler.add_job(send_daily_quote, 'interval', minutes=1)

  print("⏰ 스케줄러 실행 중... (Ctrl+C로 종료)")
  scheduler.start()