'use strict';

/**
 * paticka service
 */

const { createCoreService } = require('@strapi/strapi').factories;

module.exports = createCoreService('api::paticka.paticka');
