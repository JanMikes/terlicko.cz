FROM node:16 as dev

RUN mkdir /app && chown 1000:1000 -R /app

WORKDIR /app

COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh
ENTRYPOINT ["docker-entrypoint.sh"]

CMD ["yarn", "develop"]

EXPOSE 1337


FROM dev as prod

ENV NODE_ENV=production

COPY ./package.json ./yarn.lock ./

RUN yarn install --network-timeout 100000

COPY . .

CMD ["yarn", "start"]
