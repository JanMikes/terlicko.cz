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
COPY ./patches ./patches

RUN npm ci

COPY . .

# Bake the admin panel into the image (it used to be built on the server at
# every deploy). Domain-agnostic: Strapi bakes a relative backend URL ("/")
# that the browser resolves against its own origin.
RUN npm run build

CMD ["npm", "run", "start"]
