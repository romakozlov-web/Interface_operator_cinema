import pymysql
import pytest
DB_HOST = '134.90.167.42'
DB_PORT = 10306
DB_USER = 'Kozlov'
DB_PASSWORD = 'uwn.[H.NYJa7wxpT'
DEFAULT_DB = 'project_Kozlov'

@pytest.fixture(scope="module")
def db_connection():
    conn = pymysql.connect(
        host=DB_HOST, port=DB_PORT, user=DB_USER, password=DB_PASSWORD,
        database=DEFAULT_DB, charset="utf8mb4", cursorclass=pymysql.cursors.DictCursor
    )
    yield conn
    conn.close()

def test_film_table_structure(db_connection):
    with db_connection.cursor() as cursor:
        cursor.execute("DESCRIBE films")
        columns = {row["Field"]: row for row in cursor.fetchall()}
        required = {"id", "title", "duration", "release_date", "poster", "description"}
        assert required.issubset(columns.keys())
        assert columns["id"]["Key"] == "PRI"

def test_foreign_key_constraints(db_connection):
    """Проверка, что сеанс ссылается на существующий фильм и зал"""
    with db_connection.cursor() as cursor:
        cursor.execute("""
            SELECT s.id FROM sessions s
            LEFT JOIN films f ON s.film_id = f.id
            LEFT JOIN halls h ON s.hall_id = h.id
            WHERE f.id IS NULL OR h.id IS NULL
        """)
        orphan_sessions = cursor.fetchall()
        assert len(orphan_sessions) == 0, "Найдены сеансы с несуществующим фильмом или залом"

def test_booking_seats_not_exceed_hall_capacity(db_connection):
    """Бронирование не может превышать количество мест в зале (триггер / приложение)"""
    with db_connection.cursor() as cursor:
        # Получаем сеанс с залом, где seats = 80
        cursor.execute("""
            SELECT s.id, h.seats FROM sessions s
            JOIN halls h ON s.hall_id = h.id
            WHERE h.seats < 100 LIMIT 1
        """)
        session = cursor.fetchone()
        if not session:
            pytest.skip("Нет залов с ограниченной вместимостью")
        # Пытаемся вставить бронь с seats > вместимости
        try:
            cursor.execute(
                "INSERT INTO bookings (session_id, customer_name, seats, booking_date, status) "
                "VALUES (%s, %s, %s, NOW(), 'pending')",
                (session["id"], "Load Test", session["seats"] + 10)
            )
            db_connection.commit()
            pytest.fail("Удалось создать бронь, превышающую вместимость зала")
        except pymysql.err.IntegrityError as e:
            assert "Check constraint" in str(e) or "Data too long" in str(e)
        finally:
            db_connection.rollback()

def test_no_negative_seats(db_connection):
    """Количество мест не может быть отрицательным"""
    with db_connection.cursor() as cursor:
        with pytest.raises(pymysql.err.IntegrityError):
            cursor.execute("INSERT INTO bookings (session_id, customer_name, seats, booking_date) VALUES (1, 'Test', -5, NOW())")
            db_connection.commit()
        db_connection.rollback()

        