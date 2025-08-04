HOST_UID := $(shell id -u)
HOST_GID := $(shell id -g)

init-storage:
	@echo "Создание директорий и установка прав..."
	@mkdir -p storage/framework/views storage/logs bootstrap/cache
	@chmod -R 775 storage bootstrap
	@chown -R $(HOST_UID):$(HOST_GID) storage bootstrap
	@echo "Права установлены для UID:$(HOST_UID) GID:$(HOST_GID)"

fix-permissions:
	@echo "Исправление прав во всем проекте..."
	@find . -type d -exec chmod 775 {} \;
	@find . -type f -exec chmod 664 {} \;
	@chmod +x artisan
	@chown -R $(HOST_UID):$(HOST_GID) .
	@echo "Права исправлены"

clean:
	@echo "Полная очистка Docker..."
	@docker-compose down -v --remove-orphans
	@docker system prune -af --volumes
	@echo "Очистка завершена"

build: init-storage
	@echo "Сборка контейнеров..."
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose build

up: build
	@echo "Запуск контейнеров..."
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose up -d
	@echo "Проект запущен!"
	@echo "Проверьте доступность: http://localhost:8080"

down:
	@docker-compose down -v

shell:
	@docker-compose exec --user $(HOST_UID) reverb bash

logs:
	@docker-compose logs -f

# Экстренное исправление прав внутри контейнеров
fix-container-perms:
	@echo "Исправление прав внутри контейнеров..."
	@docker-compose exec app chmod -R 775 /var/www/storage /var/www/bootstrap
	@docker-compose exec app chown -R $(HOST_UID):$(HOST_GID) /var/www/storage /var/www/bootstrap
	@echo "Права внутри контейнеров исправлены"

# Команда для полного перезапуска
reset: clean fix-permissions up
