import pytest
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.common.keys import Keys
from webdriver_manager.chrome import ChromeDriverManager
from selenium.webdriver.chrome.service import Service
import time

@pytest.fixture(scope="module")
def driver():
    service = Service(ChromeDriverManager().install())
    options = webdriver.ChromeOptions()
    options.add_argument("--headless")  # убрать, если нужен визуальный режим
    driver = webdriver.Chrome(service=service, options=options)
    driver.implicitly_wait(5)
    yield driver
    driver.quit()

BASE_URL = "http://localhost/Interface_operator_cinema/"

def test_admin_login(driver):
    driver.get(BASE_URL)
    # Клик по кнопке "Войти как админ"
    admin_btn = WebDriverWait(driver, 10).until(
        EC.element_to_be_clickable((By.ID, "adminLoginBtn"))
    )
    admin_btn.click()
    # Ввод пароля
    password_input = WebDriverWait(driver, 10).until(
        EC.presence_of_element_located((By.ID, "adminPassword"))
    )
    password_input.send_keys("admin123")
    submit_btn = driver.find_element(By.ID, "submitPassword")
    submit_btn.click()
    # После перезагрузки страницы ждём появления элемента админ-панели
    WebDriverWait(driver, 10).until(
        EC.presence_of_element_located((By.CLASS_NAME, "admin-only"))
    )
    assert "Админ Панель" in driver.title or "админ" in driver.page_source.lower()

def test_add_film(driver):
    driver.get(BASE_URL + "?action=add_film")
    title_input = driver.find_element(By.ID, "title")
    title_input.send_keys("Selenium Test Film")
    duration_input = driver.find_element(By.ID, "duration")
    duration_input.clear()
    duration_input.send_keys("120")
    poster_input = driver.find_element(By.ID, "poster")
    poster_input.send_keys("https://example.com/poster.jpg")
    submit_btn = driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
    submit_btn.click()
    # Успешное сообщение
    WebDriverWait(driver, 10).until(
        EC.presence_of_element_located((By.CLASS_NAME, "alert-success"))
    )
    assert "Фильм добавлен" in driver.page_source

def test_user_booking(driver):
    driver.get(BASE_URL)
    # Найти ближайший сеанс и нажать "Забронировать"
    book_btn = WebDriverWait(driver, 10).until(
        EC.element_to_be_clickable((By.CSS_SELECTOR, ".film-actions .btn-success"))
    )
    book_btn.click()
    # Заполнить форму бронирования
    name_input = WebDriverWait(driver, 10).until(
        EC.presence_of_element_located((By.ID, "customer_name"))
    )
    name_input.send_keys("Тест Тестович")
    email_input = driver.find_element(By.ID, "customer_email")
    email_input.send_keys("test@selenium.com")
    seats_input = driver.find_element(By.ID, "seats")
    seats_input.clear()
    seats_input.send_keys("2")
    submit_btn = driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
    submit_btn.click()
    # Проверить успех
    WebDriverWait(driver, 10).until(
        EC.presence_of_element_located((By.CLASS_NAME, "alert-success"))
    )
    assert "Бронирование оформлено" in driver.page_source

def test_my_bookings(driver):
    driver.get(BASE_URL + "?action=my_bookings")
    email_input = WebDriverWait(driver, 10).until(
        EC.presence_of_element_located((By.ID, "email"))
    )
    email_input.send_keys("test@selenium.com")
    email_input.send_keys(Keys.RETURN)
    # Ждём появления карточек бронирований
    WebDriverWait(driver, 10).until(
        EC.presence_of_element_located((By.CLASS_NAME, "booking-card"))
    )
    assert "Selenium Test Film" in driver.page_source or "Тест" in driver.page_source

if __name__ == "__main__":
    pytest.main(["-v", __file__])