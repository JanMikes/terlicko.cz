import type { Schema, Struct } from '@strapi/strapi';

export interface ElementyBanner extends Struct.ComponentSchema {
  collectionName: 'components_komponenty_banners';
  info: {
    description: '';
    displayName: 'Obr\u00E1zek';
    icon: 'allergies';
  };
  attributes: {
    Obrazek: Schema.Attribute.Media<'images'> & Schema.Attribute.Required;
    Odkaz: Schema.Attribute.String;
  };
}

export interface ElementyDlazdice extends Struct.ComponentSchema {
  collectionName: 'components_dlazdice_dlazdices';
  info: {
    description: '';
    displayName: 'Dlazdice';
    icon: 'clone';
  };
  attributes: {
    Ikona: Schema.Attribute.Media<'images'> & Schema.Attribute.Required;
    Nadpis_dlazdice: Schema.Attribute.String & Schema.Attribute.Required;
    Odkaz: Schema.Attribute.String & Schema.Attribute.Required;
  };
}

export interface ElementyObrazekGalerie extends Struct.ComponentSchema {
  collectionName: 'components_elementy_obrazek_galeries';
  info: {
    description: '';
    displayName: 'Obr\u00E1zek galerie';
    icon: 'file-image';
  };
  attributes: {
    Nahled_obrazku: Schema.Attribute.Media<'images'>;
    Obrazek: Schema.Attribute.Media<'images'> & Schema.Attribute.Required;
    Popis: Schema.Attribute.String;
  };
}

export interface ElementyPoleFormulare extends Struct.ComponentSchema {
  collectionName: 'components_elementy_pole_formulares';
  info: {
    description: '';
    displayName: 'Pole formul\u00E1\u0159e';
    icon: 'terminal';
  };
  attributes: {
    Nadpis_pole: Schema.Attribute.String & Schema.Attribute.Required;
    Povinne: Schema.Attribute.Boolean & Schema.Attribute.Required;
    Typ: Schema.Attribute.Enumeration<
      [
        'Text',
        'Textov\u00E9 pole',
        'Email',
        'Telefon',
        'Foto',
        'Soubor',
        'Datum',
        'Datum_od_do',
        'Checkbox',
      ]
    > &
      Schema.Attribute.Required &
      Schema.Attribute.DefaultTo<'Text'>;
  };
}

export interface ElementyPoleFormulareSMoznostmi
  extends Struct.ComponentSchema {
  collectionName: 'components_elementy_pole_formulare_s_moznostmis';
  info: {
    description: '';
    displayName: 'Pole formul\u00E1\u0159e s mo\u017Enostmi';
    icon: 'grip-lines';
  };
  attributes: {
    Moznosti: Schema.Attribute.Component<'elementy.vyber-z-moznosti', true> &
      Schema.Attribute.Required;
    Nadpis_pole: Schema.Attribute.String & Schema.Attribute.Required;
    Povinne: Schema.Attribute.Boolean & Schema.Attribute.Required;
    Typ: Schema.Attribute.Enumeration<['Select', 'Checkbox list', 'Radio']> &
      Schema.Attribute.Required;
  };
}

export interface ElementySoubor extends Struct.ComponentSchema {
  collectionName: 'components_elementy_soubors';
  info: {
    displayName: 'Soubor';
    icon: 'file';
  };
  attributes: {
    Nadpis: Schema.Attribute.String & Schema.Attribute.Required;
    Soubor: Schema.Attribute.Media<'images' | 'files' | 'videos' | 'audios'> &
      Schema.Attribute.Required;
  };
}

export interface ElementyTlacitko extends Struct.ComponentSchema {
  collectionName: 'components_komponenty_tlacitkos';
  info: {
    description: '';
    displayName: 'Tla\u010D\u00EDtko';
    icon: 'angle-right';
  };
  attributes: {
    Odkaz: Schema.Attribute.String & Schema.Attribute.Required;
    Styl: Schema.Attribute.Enumeration<['Styl 1', 'Styl 2']> &
      Schema.Attribute.Required &
      Schema.Attribute.DefaultTo<'Styl 1'>;
    Text: Schema.Attribute.String & Schema.Attribute.Required;
  };
}

export interface ElementyVyberZMoznosti extends Struct.ComponentSchema {
  collectionName: 'components_elementy_vyber_z_moznosti';
  info: {
    description: '';
    displayName: 'V\u00FDb\u011Br z mo\u017Enost\u00ED';
    icon: 'align-left';
  };
  attributes: {
    Text: Schema.Attribute.String & Schema.Attribute.Required;
  };
}

export interface KomponentyAktuality extends Struct.ComponentSchema {
  collectionName: 'components_komponenty_aktualities';
  info: {
    displayName: 'Aktuality';
    icon: 'paper-plane';
  };
  attributes: {
    kategories: Schema.Attribute.Relation<'oneToMany', 'api::tagy.tagy'>;
    Pocet: Schema.Attribute.Integer &
      Schema.Attribute.Required &
      Schema.Attribute.SetMinMax<
        {
          min: 1;
        },
        number
      > &
      Schema.Attribute.DefaultTo<3>;
  };
}

export interface KomponentyFormular extends Struct.ComponentSchema {
  collectionName: 'components_komponenty_formular';
  info: {
    displayName: 'Formul\u00E1\u0159';
    icon: 'align-justify';
  };
  attributes: {
    formular: Schema.Attribute.Relation<'oneToOne', 'api::formular.formular'>;
  };
}

export interface KomponentyGalerie extends Struct.ComponentSchema {
  collectionName: 'components_komponenty_galeries';
  info: {
    description: '';
    displayName: 'Galerie';
    icon: 'images';
  };
  attributes: {
    Obrazek: Schema.Attribute.Component<'elementy.obrazek-galerie', true> &
      Schema.Attribute.Required;
    Pocet_zobrazenych: Schema.Attribute.Integer &
      Schema.Attribute.Required &
      Schema.Attribute.SetMinMax<
        {
          min: 1;
        },
        number
      > &
      Schema.Attribute.DefaultTo<6>;
  };
}

export interface KomponentyNadpis extends Struct.ComponentSchema {
  collectionName: 'components_komponenty_nadpis';
  info: {
    displayName: 'Nadpis';
    icon: 'font';
  };
  attributes: {
    Nadpis: Schema.Attribute.String & Schema.Attribute.Required;
    Typ: Schema.Attribute.Enumeration<['h2', 'h3', 'h4', 'h5']> &
      Schema.Attribute.Required &
      Schema.Attribute.DefaultTo<'h2'>;
  };
}

export interface KomponentyObrazek extends Struct.ComponentSchema {
  collectionName: 'components_komponenty_obrazeks';
  info: {
    description: '';
    displayName: 'Obr\u00E1zek';
    icon: 'image';
  };
  attributes: {
    Obrazek: Schema.Attribute.Component<'elementy.banner', true> &
      Schema.Attribute.Required &
      Schema.Attribute.SetMinMax<
        {
          max: 3;
        },
        number
      >;
  };
}

export interface KomponentyRozdelovnik extends Struct.ComponentSchema {
  collectionName: 'components_komponenty_rozdelovniks';
  info: {
    displayName: 'Rozd\u011Blovn\u00EDk';
    icon: 'minus';
  };
  attributes: {
    Tloustka_cary: Schema.Attribute.Enumeration<
      ['Norm\u00E1ln\u00ED', 'Tenk\u00E1']
    > &
      Schema.Attribute.Required &
      Schema.Attribute.DefaultTo<'Norm\u00E1ln\u00ED'>;
    Typ: Schema.Attribute.Enumeration<['Pln\u00E1', 'Te\u010Dkovan\u00E1']> &
      Schema.Attribute.Required &
      Schema.Attribute.DefaultTo<'Pln\u00E1'>;
  };
}

export interface KomponentySamosprava extends Struct.ComponentSchema {
  collectionName: 'components_samosprava_samospravas';
  info: {
    description: '';
    displayName: 'Lid\u00E9';
    icon: 'address-card';
  };
  attributes: {
    lides: Schema.Attribute.Relation<'oneToMany', 'api::lide.lide'>;
  };
}

export interface KomponentySekceSDlazdicema extends Struct.ComponentSchema {
  collectionName: 'components_dlazdice_sekce_s_dlazdicemas';
  info: {
    description: '';
    displayName: 'Dla\u017Edice';
    icon: 'th';
  };
  attributes: {
    Dlazdice: Schema.Attribute.Component<'elementy.dlazdice', true> &
      Schema.Attribute.Required;
  };
}

export interface KomponentySouboryKeStazeni extends Struct.ComponentSchema {
  collectionName: 'components_komponenty_soubory_ke_stazeni';
  info: {
    description: '';
    displayName: 'Soubory ke sta\u017Een\u00ED';
    icon: 'file-alt';
  };
  attributes: {
    Pocet_sloupcu: Schema.Attribute.Enumeration<['Jeden', 'Dva']> &
      Schema.Attribute.Required &
      Schema.Attribute.DefaultTo<'Jeden'>;
    Soubor: Schema.Attribute.Component<'elementy.soubor', true> &
      Schema.Attribute.Required;
  };
}

export interface KomponentyTextovePole extends Struct.ComponentSchema {
  collectionName: 'components_komponenty_textove_poles';
  info: {
    displayName: 'Textov\u00E9 pole';
    icon: 'text-height';
  };
  attributes: {
    Text: Schema.Attribute.RichText & Schema.Attribute.Required;
  };
}

export interface KomponentyTlacitka extends Struct.ComponentSchema {
  collectionName: 'components_komponenty_tlacitkas';
  info: {
    description: '';
    displayName: 'Tla\u010D\u00EDtka';
    icon: 'window-restore';
  };
  attributes: {
    Tlacitka: Schema.Attribute.Component<'elementy.tlacitko', true> &
      Schema.Attribute.Required;
  };
}

export interface KomponentyUredniDeska extends Struct.ComponentSchema {
  collectionName: 'components_komponenty_uredni_deskas';
  info: {
    displayName: '\u00DA\u0159edn\u00ED deska';
    icon: 'book';
  };
  attributes: {
    Kategorie: Schema.Attribute.Enumeration<
      [
        'Formul\u00E1\u0159e',
        'N\u00E1vody',
        'Odpady',
        'Rozpo\u010Dty',
        'Strategick\u00E9 dokumenty',
        '\u00DAzemn\u00ED pl\u00E1n',
        '\u00DAzemn\u00ED studie',
        'Vyhl\u00E1\u0161ky',
        'V\u00FDro\u010Dn\u00ED zpr\u00E1vy',
        '\u017Divotn\u00ED situace',
        'Poskytnut\u00E9 informace',
        'Ve\u0159ejnopr\u00E1vn\u00ED smlouvy',
        'Z\u00E1pisy z jedn\u00E1n\u00ED zastupitelstva',
        'Usnesen\u00ED rady',
        'Finan\u010Dn\u00ED v\u00FDbor',
        'Kultirn\u00ED komise',
        'Volby',
        'Projekty',
      ]
    > &
      Schema.Attribute.Required;
    Pocet: Schema.Attribute.Integer &
      Schema.Attribute.Required &
      Schema.Attribute.SetMinMax<
        {
          max: 30;
          min: 1;
        },
        number
      > &
      Schema.Attribute.DefaultTo<6>;
  };
}

declare module '@strapi/strapi' {
  export module Public {
    export interface ComponentSchemas {
      'elementy.banner': ElementyBanner;
      'elementy.dlazdice': ElementyDlazdice;
      'elementy.obrazek-galerie': ElementyObrazekGalerie;
      'elementy.pole-formulare': ElementyPoleFormulare;
      'elementy.pole-formulare-s-moznostmi': ElementyPoleFormulareSMoznostmi;
      'elementy.soubor': ElementySoubor;
      'elementy.tlacitko': ElementyTlacitko;
      'elementy.vyber-z-moznosti': ElementyVyberZMoznosti;
      'komponenty.aktuality': KomponentyAktuality;
      'komponenty.formular': KomponentyFormular;
      'komponenty.galerie': KomponentyGalerie;
      'komponenty.nadpis': KomponentyNadpis;
      'komponenty.obrazek': KomponentyObrazek;
      'komponenty.rozdelovnik': KomponentyRozdelovnik;
      'komponenty.samosprava': KomponentySamosprava;
      'komponenty.sekce-s-dlazdicema': KomponentySekceSDlazdicema;
      'komponenty.soubory-ke-stazeni': KomponentySouboryKeStazeni;
      'komponenty.textove-pole': KomponentyTextovePole;
      'komponenty.tlacitka': KomponentyTlacitka;
      'komponenty.uredni-deska': KomponentyUredniDeska;
    }
  }
}
