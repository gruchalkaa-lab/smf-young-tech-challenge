# Symfonia Young Tech Challenge - System zarządzania fakturami

Aplikacja webowa (Laravel) do zarządzania fakturami: upload plików (PDF/JPG/PNG), automatyczne rozpoznawanie tekstu (OCR), oraz ekstrakcja ustrukturyzowanych danych (kontrahent, kwota, pozycje) przy pomocy prostego agenta regułowego.

Projekt przygotowany w ramach rekrutacji Symfonia Young Tech Challenge.

## Spis treści

- Funkcjonalności
- Architektura
- Wymagania
- Instalacja
- Uruchomienie
- Dokumentacja API
- Przykładowa faktura testowa
- Agent AI - założenia i ograniczenia
- Struktura bazy danych

## Funkcjonalności

- Upload faktur w formacie PDF, JPG, JPEG, PNG (walidacja typu i rozmiaru pliku)
- Automatyczne rozpoznawanie tekstu (OCR) przy użyciu Tesseract OCR (dla obrazów) i biblioteki pdfparser (dla PDF-ów z tekstem)
- Prosty agent regułowy (oparty na wyrażeniach regularnych) do ekstrakcji danych: nazwa kontrahenta, NIP, kwota, waluta, sposób płatności
- Pełny CRUD (REST API) dla kontrahentów i faktur
- Automatyczne tworzenie/łączenie kontrahentów na podstawie NIP-u
- Interaktywna dokumentacja API (Swagger/OpenAPI)
- Baza danych SQLite (bez potrzeby konfiguracji zewnętrznego serwera bazy danych)

## Architektura

Upload pliku (Controller) -> OcrService (Tesseract / pdfparser) -> InvoiceParsingAgent (regex-based) -> Zapis do bazy (Contractor, Invoice, InvoiceItem, Payment)

Modele i relacje:
- Contractor (kontrahent) - hasMany - Invoice
- Invoice (faktura) - belongsTo Contractor, hasMany InvoiceItem, hasOne Payment
- InvoiceItem (pozycja faktury) - belongsTo Invoice
- Payment (płatność) - belongsTo Invoice

## Wymagania

- PHP 8.2+
- Composer
- Tesseract OCR (z pakietem językowym pol)
- Rozszerzenie PHP: sqlite3

## Instalacja

Sklonuj repozytorium:
git clone https://github.com/gruchalkaa-lab/smf-young-tech-challenge.git
cd smf-young-tech-challenge

Zainstaluj zależności PHP:
composer install

Skopiuj plik konfiguracyjny:
cp .env.example .env
php artisan key:generate

Zainstaluj Tesseract OCR (Ubuntu/Debian):
sudo apt update
sudo apt install -y tesseract-ocr tesseract-ocr-pol

Utwórz bazę danych SQLite:
touch database/database.sqlite

Uruchom migracje:
php artisan migrate

## Uruchomienie

php artisan serve

Aplikacja będzie dostępna pod adresem: http://127.0.0.1:8000

## Dokumentacja API

Interaktywna dokumentacja Swagger dostępna jest pod adresem:
http://127.0.0.1:8000/api/documentation

Aby wygenerować dokumentację na nowo po zmianach w kodzie:
php artisan l5-swagger:generate

### Główne endpointy

| Metoda | Endpoint | Opis |
|--------|----------|------|
| GET | /api/contractors | Lista kontrahentów |
| POST | /api/contractors | Utwórz kontrahenta |
| GET | /api/contractors/{id} | Pokaż kontrahenta |
| PUT | /api/contractors/{id} | Zaktualizuj kontrahenta |
| DELETE | /api/contractors/{id} | Usuń kontrahenta |
| GET | /api/invoices | Lista faktur |
| POST | /api/invoices | Wgraj fakturę (uruchamia OCR + agenta) |
| GET | /api/invoices/{id} | Pokaż fakturę |
| PUT | /api/invoices/{id} | Zaktualizuj fakturę |
| DELETE | /api/invoices/{id} | Usuń fakturę |

### Przykładowe żądanie (upload faktury)

curl -X POST http://127.0.0.1:8000/api/invoices -H "Accept: application/json" -F "invoice_file=@sciezka/do/faktury.pdf"

## Przykładowa faktura testowa

W folderze examples/ znajduje się przykładowy plik testowy, który można wykorzystać do sprawdzenia działania endpointu uploadu.

## Agent AI - założenia i ograniczenia

Agent (InvoiceParsingAgent) jest celowo zaprojektowany jako prosty parser regułowy oparty na wyrażeniach regularnych, a nie jako model uczenia maszynowego (np. LLM). Decyzja podyktowana była:
- ograniczonym czasem realizacji zadania,
- brakiem wymogu 100% dokładności ("agent nie musi być idealny"),
- przewidywalnością i łatwością debugowania rozwiązania regułowego.

Agent wyciąga: nazwę kontrahenta, NIP, kwotę do zapłaty, walutę, sposób płatności. Nie próbuje rozpoznawać pojedynczych pozycji faktury (invoice_items) - to naturalny kierunek dalszego rozwoju.

Możliwe kierunki rozwoju: zastąpienie/uzupełnienie logiki regułowej modelem językowym (np. lokalny model przez Ollama) przy zachowaniu tego samego interfejsu (parse(string $text): array), co pozwoliłoby na łatwą wymianę implementacji bez zmian w reszcie aplikacji.

## Struktura bazy danych

contractors: id, name, address, nip, created_at, updated_at

invoices: id, contractor_id (FK), file_path, raw_ocr_text, status (uploaded/processing/processed), created_at, updated_at

invoice_items: id, invoice_id (FK), name, quantity, unit_price, total_price, created_at, updated_at

payments: id, invoice_id (FK), amount, currency, method, paid_at, created_at, updated_at
