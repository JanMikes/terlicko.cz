/*********************************
 * Poplulate All Attributes
 *
 * https://forum.strapi.io/t/strapi-v4-populate-media-and-dynamiczones-from-components/12670/2
 *
 *********************************/

const { createCoreController } = require('@strapi/strapi/lib/factories');


const populateAttribute = (attr) => {
  const { components, repeatable } = attr;

  if (components) {
    const populate = components.reduce((currentValue, current) => {
      const [componentDir, componentName] = current.split('.');

      /* Component attributes needs to be explicitly populated */
      const componentAttributes = Object.entries(
        require(`../components/${componentDir}/${componentName}.json`)
          .attributes
      ).filter(([, v]) => v.type === 'component');

      const attrPopulates = componentAttributes.reduce(
        (acc, [curr]) => ({ ...acc, [curr]: { populate: '*' } }),
        {}
      );

      return {
        ...currentValue,
        [current.split('.').pop()]: { populate: '*' },
        ...attrPopulates,
      };
    }, {});

    return { populate };
  }

  else if (repeatable) {
    const componentName = attr.component.split('.')[1];
    const populate = { [componentName]: { populate: '*' } };
    return { populate };
  }
  return { populate: '*' };
};

const getPopulateFromSchema = function (schema) {
  //  console.log('schema', schema)
  return Object.keys(schema.attributes).reduce((currentValue, current) => {
    const attribute = schema.attributes[current];
    // console.log(attribute);
    if (!['dynamiczone', 'component'].includes(attribute.type)) {
      return { [current]: populateAttribute(attribute) };
    }
    return {
      ...currentValue,
      [current]: populateAttribute(attribute),
    };
  }, {});
};

function createPopulatedController(uid, schema) {
  return createCoreController(uid, () => {
    // console.log(schema.collectionName, getPopulateFromSchema(schema))
    return {
      async find(ctx) {
        // deeply populate all attributes with ?populate=*, else retain vanilla functionality
        if (ctx.query.populate === '*') {
          ctx.query = {
            ...ctx.query,
            populate: getPopulateFromSchema(schema),
          };
        }
        return await super.find(ctx);
      },
      async findOne(ctx) {
        if (ctx.query.populate === '*') {
          ctx.query = {
            ...ctx.query,
            populate: getPopulateFromSchema(schema),
          };
        }
        return await super.findOne(ctx);
      },
    };
  });
}

module.exports = createPopulatedController;
