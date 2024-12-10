module.exports = ({ env }) => ({
  url: '/dashboard',
  host: env('ADMIN_HOST', 'localhost'),
  salt: env('API_TOKEN_SALT'),
  auth: {
    secret: env('ADMIN_JWT_SECRET', '6022cb72a2adab1b01d9da9314c82c31'),
  },
});
