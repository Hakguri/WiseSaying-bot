from sqlalchemy import create_engine, Column, Integer, String, DateTime
from sqlalchemy.orm import declarative_base, sessionmaker
from datetime import datetime

Base = declarative_base()

class Quote(Base):
  __tablename__ = "quotes"
  id = Column(Integer, primary_key=True)
  date = Column(DateTime, default=datetime.utcnow)
  quote = Column(String, nullable=False)
  source = Column(String, default="unknown")  # "gemini" or "quotes.txt"

# SQLite DB 생성
engine = create_engine("sqlite:///quotes.db")
Base.metadata.create_all(engine)
SessionLocal = sessionmaker(bind=engine)