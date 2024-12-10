'use strict';

/**
 * lide service.
 */

const { createCoreService } = require('@strapi/strapi').factories;

module.exports = createCoreService('api::lide.lide');
