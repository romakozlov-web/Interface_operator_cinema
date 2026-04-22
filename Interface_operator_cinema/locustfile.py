from locust import HttpUser, task, between

class CinemaUser(HttpUser):
    wait_time = between(1, 3)

    @task(3)
    def view_films(self):
        self.client.get("/Interface_operator_cinema/?table=films")

    @task(2)
    def view_sessions(self):
        self.client.get("/Interface_operator_cinema/?table=sessions")

    @task(1)
    def book_ticket(self):
        # Сначала получаем список сеансов
        resp = self.client.get("/Interface_operator_cinema/?table=sessions")
        # Парсим первый session_id (упрощённо)
        # В реальности нужно извлечь id из HTML или использовать API
        session_id = 1  # заглушка
        self.client.post(
            f"/Interface_operator_cinema/?action=add_booking&session_id={session_id}",
            data={
                "customer_name": f"LoadUser{self.id}",
                "customer_email": f"user{self.id}@load.com",
                "seats": 1
            }
        )

    @task(1)
    def admin_login(self):
        self.client.post("/Interface_operator_cinema/auth.php", 
                         data={"action": "login", "password": "admin123"})