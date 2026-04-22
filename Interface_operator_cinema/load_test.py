import requests
import threading
import time
import random
from concurrent.futures import ThreadPoolExecutor

BASE_URL = "http://localhost/Interface_operator_cinema/"
THREADS = 50          # количество одновременных пользователей
REQUESTS_PER_USER = 5 # запросов на каждого

def user_actions(user_id):
    session = requests.Session()
    # 1. Главная страница
    session.get(BASE_URL)
    # 2. Страница фильмов
    session.get(BASE_URL + "?table=films")
    # 3. Получение расписания
    session.get(BASE_URL + "?table=sessions")
    # 4. Попытка бронирования (POST запрос)
    # Получим список сеансов с главной (для простоты используем статичный сеанс, например id=1)
    booking_data = {
        "session_id": "1",
        "customer_name": f"User{user_id}",
        "customer_email": f"user{user_id}@load.com",
        "seats": "1"
    }
    session.post(BASE_URL + "?action=add_booking&session_id=1", data=booking_data)
    # 5. Проверка своих броней
    session.get(BASE_URL + "?action=my_bookings&email=user{}@load.com".format(user_id))
    return True

def run_load_test():
    start_time = time.time()
    with ThreadPoolExecutor(max_workers=THREADS) as executor:
        futures = [executor.submit(user_actions, i) for i in range(THREADS * REQUESTS_PER_USER)]
        for f in futures:
            f.result()
    elapsed = time.time() - start_time
    print(f"Нагрузочный тест завершён. {THREADS * REQUESTS_PER_USER} пользователей, время: {elapsed:.2f} сек.")
    print(f"RPS: {(THREADS * REQUESTS_PER_USER) / elapsed:.2f}")

if __name__ == "__main__":
    run_load_test()