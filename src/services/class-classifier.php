<?php
/**
 * Classifier Service
 * 
 * Responsible for extracting entities, classifying news items, and inferring context
 * based on the Beiruttime OSINT Pro logic.
 * 
 * @package Beiruttime\OSINT\Services
 */

namespace Beiruttime\OSINT\Services;

use Beiruttime\OSINT\Traits\Singleton;
use Beiruttime\OSINT\Traits\Loggable;
use Beiruttime\OSINT\Utils\TextUtils;

class Classifier {
    use Singleton, Loggable;

    /**
     * Actors Matrix - Enhanced with keywords and priority weights
     * Structure: [Internal_ID => ['name' => Display Name, 'keywords' => [array], 'priority' => int]]
     */
    private array $actors = [
        // --- Local Actors ---
        'lb_army' => ['name' => 'الجيش اللبناني', 'keywords' => ['الجيش اللبناني', 'لبناني', 'جيش لبنان'], 'priority' => 10],
        'lb_security' => ['name' => 'الأمن العام', 'keywords' => ['الأمن العام', 'مديرية الأمن'], 'priority' => 9],
        'lb_resistance' => ['name' => 'المقاومة الإسلامية', 'keywords' => ['المقاومة الإسلامية', 'حزب الله', 'المقاومة', 'الوحدات'], 'priority' => 10],
        
        // --- Enemy Actors ---
        'iof' => ['name' => 'جيش العدو الإسرائيلي', 'keywords' => ['جيش العدو الإسرائيلي', 'الاحتلال', 'العدو', 'الصهيوني', 'نتنياهو', 'غانتس', 'سموتريتش', 'بن غفير', 'جيش الاحتلال', ' IDF ', ' إسرائيل '], 'priority' => 8],
        
        // --- Regional Actors ---
        'ir_gc' => ['name' => 'حرس الثورة الإيراني', 'keywords' => ['حرس الثورة', 'الحرس الثوري', 'قاآني', 'سليماني', 'فيلق القدس', 'طهران', 'مرشد', 'خامنئي'], 'priority' => 9],
        'ir_gov' => ['name' => 'الحكومة الإيرانية', 'keywords' => ['الحكومة الإيرانية', 'الرئيس الإيراني', 'بزشكيان', 'ظريف', 'وزارة الخارجية الإيرانية', 'إيران الرسمية'], 'priority' => 8],
        'sy_gov' => ['name' => 'الحكومة السورية', 'keywords' => ['سوريا', 'دمشق', 'الجيش السوري', 'بشار الأسد'], 'priority' => 7],
        'pal_hamas' => ['name' => 'حركة حماس', 'keywords' => ['حماس', 'القسام', 'غزة', 'المقاومة الفلسطينية'], 'priority' => 9],
        'pal_fatah' => ['name' => 'حركة فتح', 'keywords' => ['فتح', 'الضفة الغربية', 'عباس'], 'priority' => 7],
        
        // --- International Actors ---
        'us_gov' => ['name' => 'الولايات المتحدة', 'keywords' => ['الولايات المتحدة', 'أمريكا', 'واشنطن', 'البيت الأبيض', 'ترمب', 'ترامب', 'بايدن', 'بلينكن', 'مينendez', 'القوات الأمريكية'], 'priority' => 8],
        'uk_gov' => ['name' => 'المملكة المتحدة', 'keywords' => ['بريطانيا', 'لندن', 'المملكة المتحدة'], 'priority' => 6],
        'fr_gov' => ['name' => 'فرنسا', 'keywords' => ['فرنسا', 'باريس', 'ماكرون'], 'priority' => 6],
        'ru_gov' => ['name' => 'روسيا الاتحادية', 'keywords' => ['روسيا', 'موسكو', 'بوتين', 'لافروف'], 'priority' => 7],
        'cn_gov' => ['name' => 'الصين', 'keywords' => ['الصين', 'بكين'], 'priority' => 6],
        'pk_gov' => ['name' => 'باكستان', 'keywords' => ['باكستان', 'إسلام آباد', 'الوسيط الباكستاني'], 'priority' => 7],
        'un_org' => ['name' => 'الأمم المتحدة', 'keywords' => ['الأمم المتحدة', ' UN ', 'يونيفيل', 'مجلس الأمن'], 'priority' => 5],
        'eu_org' => ['name' => 'الاتحاد الأوروبي', 'keywords' => ['الاتحاد الأوروبي', 'أوروبا', 'بوريل'], 'priority' => 5],
        'nato_org' => ['name' => 'حلف الناتو', 'keywords' => ['الناتو', ' NATO '], 'priority' => 5],
        'brics_org' => ['name' => 'دول البريكس', 'keywords' => ['بريكس', ' BRICS '], 'priority' => 4],
    ];

    /**
     * Strategic Levels Matrix
     */
    private array $strategicLevels = [
        'strategic_general' => 'عام',
        'strategic_military' => 'عسكري',
        'strategic_security' => 'أمني',
        'strategic_political' => 'سياسي',
        'strategic_economic' => 'اقتصادي/لوجستي',
        'strategic_media' => 'إعلامي',
        'strategic_diplomatic' => 'الدبلوماسي والسياسي',
    ];

    /**
     * Tactical Levels Matrix
     */
    private array $tacticalLevels = [
        'tactical_operational' => 'عملياتي',
        'tactical_tactical' => 'تكتيكي',
        'tactical_field' => 'ميداني',
    ];

    /**
     * Regions Matrix
     */
    private array $regions = [
        'lebanon' => 'لبنان',
        'palestine' => 'فلسطين',
        'occupied' => 'الأراضي المحتلة (إسرائيل)',
        'syria' => 'سوريا',
        'iraq' => 'العراق',
        'iran' => 'إيران',
        'gulf' => 'الخليج',
        'us' => 'الولايات المتحدة',
        'europe' => 'أوروبا',
        'international' => 'دولي',
    ];

    /**
     * Weapons Matrix
     */
    private array $weapons = [
        'ballistic' => 'صواريخ بالستية',
        'cruise' => 'صواريخ كروز',
        'drones' => 'طائرات مسيرة',
        'air_strike' => 'طيران حربي',
        'artillery' => 'مدفعية',
        'small_arms' => 'أسلحة رشاشة / متوسطة',
        'naval' => 'قطع بحرية',
        'cyber' => 'هجوم إلكتروني',
        'IED' => 'عبوة ناسفة',
    ];

    /**
     * Intents Matrix
     */
    private array $intents = [
        'attack' => 'هجوم',
        'defense' => 'دفاع',
        'surveillance' => 'جمع معلومات',
        'statement' => 'تصريح',
        'negotiation' => 'مفاوضات',
        'threat' => 'تهديد',
        'exercise' => 'تدريب',
    ];

    /**
     * Extract Named Non-Military Actor
     * 
     * Improved logic to prioritize explicit mentions over contextual inference.
     * 
     * @param string $text The text to analyze
     * @return string|null The identified actor ID or null
     */
    public function extractNamedNonMilitaryActor(string $text): ?string {
        $text = TextUtils::normalizeArabic($text);
        $foundActors = [];

        // Priority 1: Exact Keyword Matching with Weight
        foreach ($this->actors as $id => $data) {
            $score = 0;
            foreach ($data['keywords'] as $keyword) {
                $normalizedKeyword = TextUtils::normalizeArabic($keyword);
                if (strpos($text, $normalizedKeyword) !== false) {
                    // Increase score based on keyword length (more specific = higher score)
                    $score += strlen($normalizedKeyword) * $data['priority'];
                    
                    // Bonus for exact phrase matches near the beginning of the text (Subject position)
                    if (strpos($text, $normalizedKeyword) < 50) {
                        $score += 20;
                    }
                }
            }
            if ($score > 0) {
                $foundActors[$id] = $score;
            }
        }

        if (empty($foundActors)) {
            return null;
        }

        // Sort by score descending
        arsort($foundActors);
        
        // Check for conflicting high scores (e.g., Iran vs USA in negotiation context)
        $topScore = reset($foundActors);
        $topActor = key($foundActors);
        
        // If multiple actors have similar high scores, apply conflict resolution
        $scores = array_values($foundActors);
        if (count($scores) > 1 && ($scores[0] - $scores[1]) < 10) {
            return $this->resolveConflictingActors(array_keys($foundActors), $text);
        }

        return $topActor;
    }

    /**
     * Resolve Conflicting Actors
     * 
     * Handles cases where multiple actors are mentioned (e.g., negotiations).
     * 
     * @param array $actorIds List of conflicting actor IDs
     * @param string $text Original text
     * @return string The resolved actor ID
     */
    private function resolveConflictingActors(array $actorIds, string $text): string {
        // Heuristic 1: Check for "Negotiation" context
        $negotiationKeywords = ['مفاوضات', 'محادثات', 'اتفاق', 'وساطة', 'اجتماع'];
        $isNegotiation = false;
        foreach ($negotiationKeywords as $kw) {
            if (strpos($text, $kw) !== false) {
                $isNegotiation = true;
                break;
            }
        }

        if ($isNegotiation) {
            // In negotiations, prefer the "Host" or the "Initiator" if mentioned, 
            // otherwise default to the first mentioned party in the text order.
            $firstPos = PHP_INT_MAX;
            $chosenActor = $actorIds[0];
            
            foreach ($actorIds as $id) {
                foreach ($this->actors[$id]['keywords'] as $kw) {
                    $pos = strpos($text, TextUtils::normalizeArabic($kw));
                    if ($pos !== false && $pos < $firstPos) {
                        $firstPos = $pos;
                        $chosenActor = $id;
                    }
                }
            }
            return $chosenActor;
        }

        // Heuristic 2: Default to the actor performing the action (Verb proximity)
        // For now, fallback to the highest priority actor defined in the matrix
        $maxPriority = -1;
        $chosenActor = $actorIds[0];
        
        foreach ($actorIds as $id) {
            if ($this->actors[$id]['priority'] > $maxPriority) {
                $maxPriority = $this->actors[$id]['priority'];
                $chosenActor = $id;
            }
        }
        
        return $chosenActor;
    }

    /**
     * Context Memory Infer
     * 
     * Infers actor based on context clues if no explicit name is found.
     * 
     * @param string $text
     * @param string|null $currentActor
     * @return string|null
     */
    public function contextMemoryInfer(string $text, ?string $currentActor): ?string {
        if ($currentActor) {
            return $currentActor;
        }

        $text = TextUtils::normalizeArabic($text);

        // Context Clues Mapping
        $contextMap = [
            'lb_resistance' => ['استهداف', 'أطلقت', 'قذائف', 'مقاومة', 'عملية نوعية'],
            'iof' => ['اغارة', 'قصف', 'احتلال', 'توغل', 'هدم', 'اعتقال'],
            'ir_gc' => ['صواريخ بالستية', 'حرس الثورة', 'تهدد', 'إغلاق مضيق'],
            'us_gov' => ['حاملة طائرات', 'البيت الأبيض', 'عقوبات', 'أسطول خامس'],
        ];

        $maxMatches = 0;
        $inferredActor = null;

        foreach ($contextMap as $actorId => $clues) {
            $matches = 0;
            foreach ($clues as $clue) {
                if (strpos($text, $clue) !== false) {
                    $matches++;
                }
            }
            if ($matches > $maxMatches) {
                $maxMatches = $matches;
                $inferredActor = $actorId;
            }
        }

        return $inferredActor;
    }

    /**
     * Governor AI
     * 
     * Final decision maker that combines extraction, inference, and rules.
     * 
     * @param array $currentResult Current classification result
     * @param string $text Original text
     * @return array Updated result
     */
    public function governorAI(array $currentResult, string $text): array {
        $explicitActor = $this->extractNamedNonMilitaryActor($text);
        $inferredActor = $this->contextMemoryInfer($text, $explicitActor);

        // Rule 1: Explicit mention always wins unless it's a generic reference
        if ($explicitActor) {
            $currentResult['actor_id'] = $explicitActor;
            $currentResult['actor_name'] = $this->actors[$explicitActor]['name'];
            $currentResult['confidence'] = 'high';
        } elseif ($inferredActor) {
            $currentResult['actor_id'] = $inferredActor;
            $currentResult['actor_name'] = $this->actors[$inferredActor]['name'];
            $currentResult['confidence'] = 'medium';
        } else {
            $currentResult['confidence'] = 'low';
        }

        // Rule 2: Validate Region against Actor
        // Example: If Actor is 'iof' and Region is 'Tehran', check if it's an attack statement or physical presence
        if (!empty($currentResult['region_id'])) {
            // Add specific validation logic here if needed
        }

        return $currentResult;
    }

    /**
     * Force Requested Actor Rule
     * 
     * Allows manual override or rule-based forcing of an actor.
     * 
     * @param string $forcedActorId
     * @param array $currentResult
     * @return array
     */
    public function forceRequestedActorRule(string $forcedActorId, array $currentResult): array {
        if (isset($this->actors[$forcedActorId])) {
            $currentResult['actor_id'] = $forcedActorId;
            $currentResult['actor_name'] = $this->actors[$forcedActorId]['name'];
            $currentResult['override'] = true;
        }
        return $currentResult;
    }

    /**
     * Get Actor Name by ID
     */
    public function getActorName(string $id): string {
        return $this->actors[$id]['name'] ?? 'غير محدد';
    }

    /**
     * Get All Actors
     */
    public function getAllActors(): array {
        return $this->actors;
    }
}
