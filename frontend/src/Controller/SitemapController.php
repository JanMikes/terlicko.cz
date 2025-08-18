<?php
declare(strict_types=1);

namespace Terlicko\Web\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Terlicko\Web\Services\Strapi\StrapiLinkHelper;
use Terlicko\Web\Value\Content\Data\SekceData;

final class SitemapController extends AbstractController
{
    public function __construct(
        readonly private StrapiLinkHelper $linkHelper
    ) {}

    #[Route(path: '/mapa-stranek', name: 'sitemap')]
    public function __invoke(): Response
    {
        $sections = $this->linkHelper->getSections();
        
        // Build hierarchy tree
        $tree = $this->buildSectionTree($sections);
        
        return $this->render('sitemap.html.twig', [
            'sectionTree' => $tree,
        ]);
    }
    
    /**
     * @param array<string, SekceData> $sections
     * @return array<string, array{section: SekceData, children: array<string, mixed>, link: string}>
     */
    private function buildSectionTree(array $sections): array
    {
        $tree = [];
        $indexed = [];
        
        // First pass: create indexed array with children arrays
        foreach ($sections as $slug => $section) {
            $indexed[$slug] = [
                'section' => $section,
                'children' => [],
                'link' => $this->linkHelper->getLinkForSlug($slug)
            ];
        }
        
        // Second pass: build tree structure
        foreach ($indexed as $slug => $data) {
            $section = $data['section'];
            
            if ($section->parentSlug === null) {
                // Top level section
                $tree[$slug] = &$indexed[$slug];
            } else {
                // Child section - add to parent's children
                if (isset($indexed[$section->parentSlug])) {
                    $indexed[$section->parentSlug]['children'][$slug] = &$indexed[$slug];
                }
            }
        }
        
        return $tree;
    }
}
