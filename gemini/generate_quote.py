import os
import google.generativeai as genai
from dotenv import load_dotenv
from pathlib import Path

# .env에서 API 키 로딩
dotenv_path = Path(__file__).resolve().parent.parent / ".env"
load_dotenv(dotenv_path)

# Gemini API 키 설정
genai.configure(api_key=os.getenv("GEMINI_API_KEY"))

def generate_investment_quote():
    model = genai.GenerativeModel("gemini-1.5-pro-latest")  # 최신 모델 이름으로 변경
    prompt = "투자 대가의 말투로 된 인사이트 있는 투자 명언 한 줄을 한국어로 생성해줘."
    response = model.generate_content(prompt)
    return response.text.strip()