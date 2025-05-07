from bot.models import SessionLocal, Quote

def save_quote_to_db(quote_text, source):
  db = SessionLocal()
  new_quote = Quote(quote=quote_text, source=source)
  db.add(new_quote)
  db.commit()
  db.close()