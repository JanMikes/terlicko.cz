<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

enum UredniDeskaKategorieField
{
    case Zobrazit_v_formulare;
    case Zobrazit_v_navody;
    case Zobrazit_v_odpady;
    case Zobrazit_v_rozpocty;
    case Zobrazit_v_strategicke_dokumenty;
    case Zobrazit_v_uzemni_plan;
    case Zobrazit_v_uzemni_studie;
    case Zobrazit_v_vyhlasky;
    case Zobrazit_v_vyrocni_zpravy;
    case Zobrazit_v_zivotni_situace;
    case Zobrazit_v_poskytnute_informace;
    case Zobrazit_v_verejnopravni_smlouvy;
    case Zobrazit_v_usneseni_rady;
    case Zobrazit_v_financni_vybor;
    case Zobrazit_v_kulturni_komise;
    case Zobrazit_v_volby;
    case Zobrazit_v_projekty;


    public function toKategorie(): KategorieUredniDesky
    {
        return match($this) {
            self::Zobrazit_v_formulare => KategorieUredniDesky::Formulare,
            self::Zobrazit_v_navody => KategorieUredniDesky::Navody,
            self::Zobrazit_v_odpady => KategorieUredniDesky::Odpady,
            self::Zobrazit_v_rozpocty => KategorieUredniDesky::Rozpocty,
            self::Zobrazit_v_strategicke_dokumenty => KategorieUredniDesky::Strategicke_dokumenty,
            self::Zobrazit_v_uzemni_plan => KategorieUredniDesky::Uzemni_plan,
            self::Zobrazit_v_uzemni_studie => KategorieUredniDesky::Uzemni_studie,
            self::Zobrazit_v_vyhlasky => KategorieUredniDesky::Vyhlasky,
            self::Zobrazit_v_vyrocni_zpravy => KategorieUredniDesky::Vyrocni_zpravy,
            self::Zobrazit_v_zivotni_situace => KategorieUredniDesky::Zivotni_situace,
            self::Zobrazit_v_poskytnute_informace => KategorieUredniDesky::Poskytnute_informace,
            self::Zobrazit_v_verejnopravni_smlouvy => KategorieUredniDesky::Verejnopravni_smlouvy,
            self::Zobrazit_v_usneseni_rady => KategorieUredniDesky::Usneseni_rady,
            self::Zobrazit_v_financni_vybor => KategorieUredniDesky::Financni_vybor,
            self::Zobrazit_v_kulturni_komise => KategorieUredniDesky::Kulturni_komise,
            self::Zobrazit_v_volby => KategorieUredniDesky::Volby,
            self::Zobrazit_v_projekty => KategorieUredniDesky::Projekty,
        };
    }
}
