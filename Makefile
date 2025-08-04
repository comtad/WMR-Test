HOST_UID := $(shell id -u)
HOST_GID := $(shell id -g)
DOCKER_COMPOSE = HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose

# Основная команда: запуск всего проекта
start: init-storage fix-permissions build up wait-for-db composer-install key-generate migrate
	@echo "🚀 Проект полностью готов к работе!"
	@echo "👉 Проверьте: http://localhost:80"

# Инициализация хранилища
init-storage:
	@echo "Создание системных директорий..."
	@mkdir -p storage/framework/{cache,sessions,testing,views} storage/logs bootstrap/cache
	@chmod -R 775 storage bootstrap
	@chown -R $(HOST_UID):$(HOST_GID) storage bootstrap
	@echo "✅ Права установлены для UID:$(HOST_UID) GID:$(HOST_GID)"

# Исправление прав доступа
fix-permissions:
	@echo "Установка корректных прав..."
	@find . -type d -exec chmod 775 {} \;
	@find . -type f -exec chmod 664 {} \;
	@chmod +x artisan
	@chown -R $(HOST_UID):$(HOST_GID) .
	@echo "✅ Права установлены"

# Сборка контейнеров
build:
	@echo "🔨 Сборка Docker-контейнеров..."
	@$(DOCKER_COMPOSE) build --pull
	@echo "✅ Контейнеры собраны"

# Запуск контейнеров
up:
	@echo "🚀 Запуск Docker-контейнеров..."
	@$(DOCKER_COMPOSE) up -d --remove-orphans
	@echo "✅ Контейнеры запущены"

# Ожидание готовности базы данных
wait-for-db:
	@echo "⏳ Ожидание готовности базы данных (макс 30 сек)..."
	@$(DOCKER_COMPOSE) exec -T db sh -c 'for i in `seq 1 30`; do if mysqladmin ping -h"127.0.0.1" -u"$${DB_USERNAME}" -p"$${DB_PASSWORD}" --silent; then exit 0; fi; sleep 1; done; echo "Database connection failed"; exit 1'
	@echo "✅ База данных готова"

# Установка зависимостей
composer-install:
	@echo "📦 Установка Composer-зависимостей..."
	@$(DOCKER_COMPOSE) exec -T app composer install --no-interaction --optimize-autoloader
	@echo "✅ Зависимости установлены"

# Генерация ключа приложения
key-generate:
	@echo "🔑 Генерация APP_KEY..."
	@$(DOCKER_COMPOSE) exec -T app php artisan key:generate --force
	@echo "✅ Ключ сгенерирован"

# Выполнение миграций
migrate:
	@echo "🔄 Выполнение миграций базы данных..."
	@$(DOCKER_COMPOSE) exec -T app php artisan migrate --force
	@echo "✅ Миграции выполнены"

# Остановка контейнеров
down:
	@echo "🛑 Остановка контейнеров..."
	@$(DOCKER_COMPOSE) down -v
	@echo "✅ Контейнеры остановлены"

# Очистка Docker
clean:
	@echo "🧹 Полная очистка Docker..."
	@$(DOCKER_COMPOSE) down -v --remove-orphans
	@docker system prune -af --volumes
	@echo "✅ Очистка завершена"

# Перезапуск проекта
reset: clean start

# Получение доступа к консоли
shell:
	@$(DOCKER_COMPOSE) exec --user $(HOST_UID) app bash

# Просмотр логов
logs:
	@$(DOCKER_COMPOSE) logs -f --tail=100

# Исправление прав внутри контейнеров
fix-container-perms:
	@echo "🔧 Исправление прав внутри контейнеров..."
	@$(DOCKER_COMPOSE) exec app chown -R $(HOST_UID):$(HOST_GID) /var/www/storage /var/www/bootstrap
	@echo "✅ Права исправлены"

# Помощь по командам
help:
	@echo "Доступные команды:"
	@echo "  make start      - Полный запуск проекта (после клонирования)"
	@echo "  make reset      - Полная пересборка проекта"
	@echo "  make down       - Остановка контейнеров"
	@echo "  make shell      - Доступ к консоли приложения"
	@echo "  make logs       - Просмотр логов в реальном времени"
	@echo "  make clean      - Полная очистка Docker (контейнеры, образы, тома)"
	@echo "  make help       - Показать эту справку"

.PHONY: start init-storage fix-permissions build up wait-for-db composer-install key-generate migrate down clean reset shell logs fix-container-perms help
