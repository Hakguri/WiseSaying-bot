import os
import requests
from dotenv import load_dotenv

load_dotenv()

TOKEN = os.getenv("TELEGRAM_TOKEN")
CHAT_ID = os.getenv("TELEGRAM_CHAT_ID")
MESSAGE = "📢 투자 명언 봇 테스트 메시지입니다."

def send_message():
  url = f"https://api.telegram.org/bot{TOKEN}/sendMessage"
  data = {
    "chat_id": int(CHAT_ID),
    "text": MESSAGE
  }
  response = requests.post(url, data=data)
  print(response.json())

if __name__ == "__main__":
  send_message()