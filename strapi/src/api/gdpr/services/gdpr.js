'use strict';

/**
 * gdpr service.
 */

const { createCoreService } = require('@strapi/strapi').factories;

module.exports = createCoreService('api::gdpr.gdpr');
