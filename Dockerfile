FROM sail-8.4/app

RUN apt-get update && apt-get install -y supervisor

COPY _docker/supervisor/ /etc/supervisor/conf.d/

COPY _docker/start-container.sh /usr/local/bin/start-container
RUN chmod +x /usr/local/bin/start-container

EXPOSE 8080

CMD ["start-container"]
