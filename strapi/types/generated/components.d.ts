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
    Odkaz: Schema.Attribute.Component<'elementy.odkaz', false>;
  };
}

export interface ElementyClovekSamospravy extends Struct.ComponentSchema {
  collectionName: 'components_elementy_clovek_samospravies';
  info: {
    displayName: '\u010Clov\u011Bk samospr\u00E1vy';
    icon: 'user';
  };
  attributes: {
    Funkce: Schema.Attribute.String;
    lide: Schema.Attribute.Relation<'oneToOne', 'api::lide.lide'>;
  };
}

export interface ElementyDatum extends Struct.ComponentSchema {
  collectionName: 'components_elementy_data';
  info: {
    displayName: 'Datum';
    icon: 'calendar';
  };
  attributes: {
    Datum: Schema.Attribute.DateTime & Schema.Attribute.Required;
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
    Odkaz: Schema.Attribute.Component<'elementy.odkaz', false> &
      Schema.Attribute.Required;
  };
}

export interface ElementyFilm extends Struct.ComponentSchema {
  collectionName: 'components_elementy_films';
  info: {
    displayName: 'Film';
    icon: 'television';
  };
  attributes: {
    Datumy: Schema.Attribute.Component<'elementy.datum', true>;
    Film: Schema.Attribute.String & Schema.Attribute.Required;
    Obrazek: Schema.Attribute.Media<'images' | 'files'>;
    Popis: Schema.Attribute.String;
    Vstupne: Schema.Attribute.String & Schema.Attribute.Required;
  };
}

export interface ElementyKarta extends Struct.ComponentSchema {
  collectionName: 'components_elementy_kartas';
  info: {
    description: '';
    displayName: 'Karta';
    icon: 'file';
  };
  attributes: {
    Adresa: Schema.Attribute.String;
    Email: Schema.Attribute.Email;
    Nazev: Schema.Attribute.String & Schema.Attribute.Required;
    Obrazek: Schema.Attribute.Media<'images' | 'files'>;
    Odkaz: Schema.Attribute.String;
    Odkaz_na_mapu: Schema.Attribute.String;
    Telefon: Schema.Attribute.String;
  };
}

export interface ElementyLekar extends Struct.ComponentSchema {
  collectionName: 'components_elementy_lekar';
  info: {
    displayName: 'L\u00E9ka\u0159';
    icon: 'wheelchair';
  };
  attributes: {
    Jmeno: Schema.Attribute.String & Schema.Attribute.Required;
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
    Obrazek: Schema.Attribute.Media<'images'> & Schema.Attribute.Required;
    Popis: Schema.Attribute.String;
  };
}

export interface ElementyOdkaz extends Struct.ComponentSchema {
  collectionName: 'components_elementy_odkazs';
  info: {
    description: '';
    displayName: 'Odkaz';
    icon: 'code';
  };
  attributes: {
    sekce: Schema.Attribute.Relation<'oneToOne', 'api::sekce.sekce'>;
    Soubor: Schema.Attribute.Media<'images' | 'files' | 'videos' | 'audios'>;
    URL: Schema.Attribute.String;
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
    Napoveda: Schema.Attribute.String;
    Povinne: Schema.Attribute.Boolean &
      Schema.Attribute.Required &
      Schema.Attribute.DefaultTo<true>;
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
    Vyplnuje_urad: Schema.Attribute.Boolean & Schema.Attribute.DefaultTo<false>;
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
    Napoveda: Schema.Attribute.String;
    Povinne: Schema.Attribute.Boolean &
      Schema.Attribute.Required &
      Schema.Attribute.DefaultTo<true>;
    Typ: Schema.Attribute.Enumeration<['Select', 'Checkbox list', 'Radio']> &
      Schema.Attribute.Required;
    Vyplnuje_urad: Schema.Attribute.Boolean & Schema.Attribute.DefaultTo<false>;
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

export interface ElementyTelefon extends Struct.ComponentSchema {
  collectionName: 'components_elementy_telefons';
  info: {
    description: '';
    displayName: 'Telefon';
    icon: 'phone';
  };
  attributes: {
    Nazev_telefonu: Schema.Attribute.String;
    Telefon: Schema.Attribute.String & Schema.Attribute.Required;
  };
}

export interface ElementyTerminAkce extends Struct.ComponentSchema {
  collectionName: 'components_elementy_termin_akces';
  info: {
    description: '';
    displayName: 'Term\u00EDn akce';
    icon: 'calendar';
  };
  attributes: {
    Nazev: Schema.Attribute.String;
    Termin: Schema.Attribute.DateTime & Schema.Attribute.Required;
    Zaznam: Schema.Attribute.String;
    Zivy_prenos: Schema.Attribute.String;
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
    Odkaz: Schema.Attribute.Component<'elementy.odkaz', false> &
      Schema.Attribute.Required;
    Styl: Schema.Attribute.Enumeration<['Styl 1', 'Styl 2']> &
      Schema.Attribute.Required &
      Schema.Attribute.DefaultTo<'Styl 1'>;
    Text: Schema.Attribute.String & Schema.Attribute.Required;
  };
}

export interface ElementyVizitka extends Struct.ComponentSchema {
  collectionName: 'components_elementy_vizitkas';
  info: {
    description: '';
    displayName: 'Vizitka';
    icon: 'briefcase';
  };
  attributes: {
    Adresa: Schema.Attribute.String;
    Lekari: Schema.Attribute.Component<'elementy.lekar', true>;
    Odkaz: Schema.Attribute.String;
    Odkaz_na_mapu: Schema.Attribute.String;
    Oteviraci_doba: Schema.Attribute.Text;
    Telefony: Schema.Attribute.Component<'elementy.telefon', true>;
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
    description: '';
    displayName: 'Aktuality';
    icon: 'seed';
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
    description: '';
    displayName: 'Formul\u00E1\u0159';
    icon: 'discuss';
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
    icon: 'landscape';
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

export interface KomponentyKarty extends Struct.ComponentSchema {
  collectionName: 'components_komponenty_karties';
  info: {
    displayName: 'Karty';
    icon: 'stack';
  };
  attributes: {
    Karty: Schema.Attribute.Component<'elementy.karta', true>;
  };
}

export interface KomponentyNadpis extends Struct.ComponentSchema {
  collectionName: 'components_komponenty_nadpis';
  info: {
    description: '';
    displayName: 'Nadpis';
    icon: 'bold';
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
    icon: 'picture';
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

export interface KomponentyProgramKina extends Struct.ComponentSchema {
  collectionName: 'components_komponenty_program_kinas';
  info: {
    displayName: 'Program kina';
    icon: 'television';
  };
  attributes: {
    Filmy: Schema.Attribute.Component<'elementy.film', true>;
  };
}

export interface KomponentyRozdelovnik extends Struct.ComponentSchema {
  collectionName: 'components_komponenty_rozdelovniks';
  info: {
    description: '';
    displayName: 'Rozd\u011Blovn\u00EDk';
    icon: 'oneToOne';
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
    icon: 'user';
  };
  attributes: {
    Lide: Schema.Attribute.Component<'elementy.clovek-samospravy', true>;
  };
}

export interface KomponentySekceSDlazdicema extends Struct.ComponentSchema {
  collectionName: 'components_dlazdice_sekce_s_dlazdicemas';
  info: {
    description: '';
    displayName: 'Dla\u017Edice';
    icon: 'apps';
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
    icon: 'attachment';
  };
  attributes: {
    Pocet_sloupcu: Schema.Attribute.Enumeration<['Jeden', 'Dva']> &
      Schema.Attribute.Required &
      Schema.Attribute.DefaultTo<'Jeden'>;
    Soubor: Schema.Attribute.Component<'elementy.soubor', true> &
      Schema.Attribute.Required;
  };
}

export interface KomponentyTerminyAkci extends Struct.ComponentSchema {
  collectionName: 'components_komponenty_terminy_akci';
  info: {
    displayName: 'Term\u00EDny akc\u00ED';
    icon: 'calendar';
  };
  attributes: {
    Terminy: Schema.Attribute.Component<'elementy.termin-akce', true>;
  };
}

export interface KomponentyTextovePole extends Struct.ComponentSchema {
  collectionName: 'components_komponenty_textove_poles';
  info: {
    description: '';
    displayName: 'Textov\u00E9 pole';
    icon: 'layer';
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
    icon: 'cursor';
  };
  attributes: {
    Tlacitka: Schema.Attribute.Component<'elementy.tlacitko', true> &
      Schema.Attribute.Required;
  };
}

export interface KomponentyUredniDeska extends Struct.ComponentSchema {
  collectionName: 'components_komponenty_uredni_deskas';
  info: {
    description: '';
    displayName: '\u00DA\u0159edn\u00ED deska';
    icon: 'pin';
  };
  attributes: {
    kategorie_uredni_deskies: Schema.Attribute.Relation<
      'oneToMany',
      'api::kategorie-uredni-desky.kategorie-uredni-desky'
    >;
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

export interface KomponentyVizitky extends Struct.ComponentSchema {
  collectionName: 'components_komponenty_vizitkies';
  info: {
    displayName: 'Vizitky';
    icon: 'briefcase';
  };
  attributes: {
    Vizitky: Schema.Attribute.Component<'elementy.vizitka', true>;
  };
}

declare module '@strapi/strapi' {
  export module Public {
    export interface ComponentSchemas {
      'elementy.banner': ElementyBanner;
      'elementy.clovek-samospravy': ElementyClovekSamospravy;
      'elementy.datum': ElementyDatum;
      'elementy.dlazdice': ElementyDlazdice;
      'elementy.film': ElementyFilm;
      'elementy.karta': ElementyKarta;
      'elementy.lekar': ElementyLekar;
      'elementy.obrazek-galerie': ElementyObrazekGalerie;
      'elementy.odkaz': ElementyOdkaz;
      'elementy.pole-formulare': ElementyPoleFormulare;
      'elementy.pole-formulare-s-moznostmi': ElementyPoleFormulareSMoznostmi;
      'elementy.soubor': ElementySoubor;
      'elementy.telefon': ElementyTelefon;
      'elementy.termin-akce': ElementyTerminAkce;
      'elementy.tlacitko': ElementyTlacitko;
      'elementy.vizitka': ElementyVizitka;
      'elementy.vyber-z-moznosti': ElementyVyberZMoznosti;
      'komponenty.aktuality': KomponentyAktuality;
      'komponenty.formular': KomponentyFormular;
      'komponenty.galerie': KomponentyGalerie;
      'komponenty.karty': KomponentyKarty;
      'komponenty.nadpis': KomponentyNadpis;
      'komponenty.obrazek': KomponentyObrazek;
      'komponenty.program-kina': KomponentyProgramKina;
      'komponenty.rozdelovnik': KomponentyRozdelovnik;
      'komponenty.samosprava': KomponentySamosprava;
      'komponenty.sekce-s-dlazdicema': KomponentySekceSDlazdicema;
      'komponenty.soubory-ke-stazeni': KomponentySouboryKeStazeni;
      'komponenty.terminy-akci': KomponentyTerminyAkci;
      'komponenty.textove-pole': KomponentyTextovePole;
      'komponenty.tlacitka': KomponentyTlacitka;
      'komponenty.uredni-deska': KomponentyUredniDeska;
      'komponenty.vizitky': KomponentyVizitky;
    }
  }
}
