FROM node:20 AS dev

RUN mkdir /app && chown 1000:1000 -R /app

WORKDIR /app

COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh
ENTRYPOINT ["docker-entrypoint.sh"]

CMD ["npm", "run", "develop"]

EXPOSE 1337


FROM dev AS prod

ENV NODE_ENV=production

COPY ./package.json ./package-lock.json ./

RUN npm ci

COPY . .

CMD ["npm", "run", "start"]
