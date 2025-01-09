module.exports = ({ env }) => ({
  auth: {
    secret: env('ADMIN_JWT_SECRET', '6022cb72a2adab1b01d9da9314c82c31'),
  },
  apiToken: {
    salt: env('API_TOKEN_SALT', 'someRandomLongString'),
  },
  transfer: {
    token: {
      salt: env('TRANSFER_TOKEN_SALT', 'anotherRandomLongString'),
    }
  },
});
