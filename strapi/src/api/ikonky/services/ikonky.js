'use strict';

/**
 * ikonky service
 */

const { createCoreService } = require('@strapi/strapi').factories;

module.exports = createCoreService('api::ikonky.ikonky');
