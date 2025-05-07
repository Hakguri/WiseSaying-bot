import random
from pathlib import Path

def get_random_quote():
  quotes_path = Path(__file__).resolve().parent / 'quotes.txt'
  with open(quotes_path, 'r', encoding='utf-8') as f:
    quotes = f.readlines()
  return random.choice(quotes).strip()