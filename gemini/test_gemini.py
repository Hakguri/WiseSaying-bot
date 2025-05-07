import sys
from pathlib import Path
sys.path.append(str(Path(__file__).resolve().parent.parent))

from gemini.generate_quote import generate_investment_quote

print(generate_investment_quote())