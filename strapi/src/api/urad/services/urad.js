'use strict';

/**
 * urad service.
 */

const { createCoreService } = require('@strapi/strapi').factories;

module.exports = createCoreService('api::urad.urad');
