<?php
/**
 * Resource -> Resource relationship scoring.
 *
 * WEB-007.4 / 4.2A-D.6
 *
 * Scores are derived editorial intelligence. They are never persisted as the
 * canonical relationship. Final Related Resource relationships remain ordered
 * Resource IDs selected by the editor.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Frozen Resource -> Resource scoring weights.
 *
 * @return array<string,int>
 */
function tnt_resource_resource_scoring_weights() {
    return array(
        'tags'    => 30,
        'topics'  => 25,
        'title'   => 20,
        'content' => 15,
        'type'    => 10,
    );
}

/**
 * Frozen qualification threshold.
 *
 * Automatic preselection is implemented separately after runtime validation.
 *
 * @return int
 */
function tnt_resource_resource_qualification_threshold() {
    return 55;
}

/**
 * Frozen automatic preselection limit.
 *
 * This is a recommendation limit, not a hard limit on manually curated
 * Related Resources.
 *
 * @return int
 */
function tnt_resource_resource_preselection_limit() {
    return 3;
}

/**
 * Get the single Resource Type slug for a Resource.
 *
 * Published Resources should have exactly one type according to the frozen
 * editorial contract. If data is incomplete, the first valid term is used and
 * missing data contributes no type score.
 *
 * @param int $resource_id Resource ID.
 * @return string
 */
function tnt_get_resource_scoring_type_slug( $resource_id ) {

    $terms = wp_get_post_terms(
        absint( $resource_id ),
        'resource_type',
        array( 'fields' => 'slugs' )
    );

    if ( is_wp_error( $terms ) || empty( $terms ) ) {
        return '';
    }

    return sanitize_key( (string) reset( $terms ) );
}

/**
 * Frozen directional Resource Type compatibility matrix.
 *
 * Values are normalized 0..1 multipliers applied to the 10-point Type weight.
 *
 * Article -> Article       50%
 * Article -> How-To       100%
 * Article -> Tutorial      75%
 *
 * How-To -> Article        50%
 * How-To -> How-To         75%
 * How-To -> Tutorial      100%
 *
 * Tutorial -> Article      50%
 * Tutorial -> How-To      100%
 * Tutorial -> Tutorial     75%
 *
 * @param string $source_type    Current Resource Type slug.
 * @param string $candidate_type Candidate Resource Type slug.
 * @return float
 */
function tnt_resource_type_compatibility_ratio( $source_type, $candidate_type ) {

    $source_type    = sanitize_key( $source_type );
    $candidate_type = sanitize_key( $candidate_type );

    $matrix = array(
        'article' => array(
            'article'      => 0.50,
            'how-to-guide' => 1.00,
            'tutorial'     => 0.75,
        ),
        'how-to-guide' => array(
            'article'      => 0.50,
            'how-to-guide' => 0.75,
            'tutorial'     => 1.00,
        ),
        'tutorial' => array(
            'article'      => 0.50,
            'how-to-guide' => 1.00,
            'tutorial'     => 0.75,
        ),
    );

    if (
        ! isset( $matrix[ $source_type ] )
        || ! isset( $matrix[ $source_type ][ $candidate_type ] )
    ) {
        return 0.0;
    }

    return (float) $matrix[ $source_type ][ $candidate_type ];
}

/**
 * Build normalized scoring context for a Resource.
 *
 * Reuses the generic semantic helpers established and validated by the frozen
 * Related Tool scoring engine (C.1 monotonic evidence + C.2 noise filtering).
 *
 * @param WP_Post $resource Resource post.
 * @return array<string,mixed>
 */
function tnt_get_resource_resource_scoring_context( WP_Post $resource ) {

    $tag_tokens = tnt_resource_scoring_distinctive_tokens(
        tnt_resource_scoring_taxonomy_tokens( $resource->ID, 'resource_tag' )
    );

    $topic_tokens = tnt_resource_scoring_distinctive_tokens(
        tnt_resource_scoring_taxonomy_tokens( $resource->ID, 'tool_category' )
    );

    $title_tokens = tnt_resource_scoring_tokens( get_the_title( $resource ) );

    $content_tokens = array_values(
        array_unique(
            array_merge(
                tnt_resource_scoring_tokens( get_the_excerpt( $resource ) ),
                tnt_resource_scoring_tokens( $resource->post_content )
            )
        )
    );

    $category_ids = wp_get_object_terms(
        $resource->ID,
        'tool_category',
        array( 'fields' => 'ids' )
    );
    $category_ids = is_wp_error( $category_ids ) ? array() : array_values( array_map( 'absint', $category_ids ) );

    return array(
        'resource_id'    => $resource->ID,
        'category_ids'   => $category_ids,
        'type_slug'      => tnt_get_resource_scoring_type_slug( $resource->ID ),
        'tag_tokens'     => $tag_tokens,
        'topic_tokens'   => $topic_tokens,
        'title_tokens'   => $title_tokens,
        'content_tokens' => $content_tokens,
    );
}

/**
 * Calculate frozen Resource -> Resource relevance score.
 *
 * @param WP_Post|int $resource  Current Resource object or ID.
 * @param WP_Post|int $candidate Candidate Resource object or ID.
 * @return array<string,mixed>|null Structured score result.
 */
function tnt_calculate_resource_resource_score( $resource, $candidate ) {

    $resource  = $resource instanceof WP_Post ? $resource : get_post( absint( $resource ) );
    $candidate = $candidate instanceof WP_Post ? $candidate : get_post( absint( $candidate ) );

    if (
        ! $resource instanceof WP_Post
        || 'resource' !== $resource->post_type
        || ! $candidate instanceof WP_Post
        || 'resource' !== $candidate->post_type
        || 'publish' !== $candidate->post_status
        || $resource->ID === $candidate->ID
    ) {
        return null;
    }

    $weights = tnt_resource_resource_scoring_weights();

    $source_context    = tnt_get_resource_resource_scoring_context( $resource );
    $candidate_context = tnt_get_resource_resource_scoring_context( $candidate );

    /*
     * 1. Shared granular Resource Tags (max 30).
     * Monotonic coverage means adding another genuinely shared tag can never
     * dilute relevance already earned.
     */
    $tag_score = tnt_resource_scoring_weighted_value(
        tnt_resource_scoring_coverage(
            $source_context['tag_tokens'],
            $candidate_context['tag_tokens']
        ),
        $weights['tags']
    );

    /*
     * 2. Shared canonical Resource Topics / Categories (max 25).
     */
    $shared_category_ids = array_intersect(
        $source_context['category_ids'],
        $candidate_context['category_ids']
    );

    $topic_score = ! empty( $shared_category_ids )
        ? (float) $weights['topics']
        : 0.0;

    /*
     * 3. Direct title semantic similarity (max 20).
     */
    $title_score = tnt_resource_scoring_weighted_value(
        tnt_resource_scoring_similarity(
            $source_context['title_tokens'],
            $candidate_context['title_tokens']
        ),
        $weights['title']
    );

    /*
     * 4. Broader content/excerpt semantic similarity (max 15).
     */
    $content_score = tnt_resource_scoring_weighted_value(
        tnt_resource_scoring_similarity(
            $source_context['content_tokens'],
            $candidate_context['content_tokens']
        ),
        $weights['content']
    );

    /*
     * 5. Directional reader-journey Type compatibility (max 10).
     */
    $type_score = tnt_resource_scoring_weighted_value(
        tnt_resource_type_compatibility_ratio(
            $source_context['type_slug'],
            $candidate_context['type_slug']
        ),
        $weights['type']
    );

    $signals = array(
        'tags'    => $tag_score,
        'topics'  => $topic_score,
        'title'   => $title_score,
        'content' => $content_score,
        'type'    => $type_score,
    );

    $substantive_score = $tag_score + $topic_score + $title_score + $content_score;
    $score             = max( 0, min( 100, array_sum( $signals ) ) );

    /*
     * Type compatibility may enhance an already meaningful relationship but
     * can never qualify a relationship by itself.
     */
    $qualified = (
        $substantive_score > 0
        && $score >= tnt_resource_resource_qualification_threshold()
    );

    return array(
        'resource_id'        => $resource->ID,
        'candidate_id'       => $candidate->ID,
        'candidate_type'     => 'resource',
        'score'              => $score,
        'qualified'          => $qualified,
        'structured_score'   => $tag_score + $topic_score,
        'substantive_score'  => $substantive_score,
        'signals'            => $signals,
        'published_timestamp'=> (int) get_post_time( 'U', true, $candidate ),
    );
}

/**
 * Get all published Resource candidates ranked for a Resource.
 *
 * Frozen deterministic tie-breaking:
 * 1. Final score DESC
 * 2. Structured semantic subtotal (Tags + Topics) DESC
 * 3. Resource Tag contribution DESC
 * 4. Resource Topic contribution DESC
 * 5. Title semantic contribution DESC
 * 6. Publication date DESC
 * 7. Resource title ASC
 * 8. WordPress Resource ID ASC
 *
 * @param WP_Post|int $resource Resource object or ID.
 * @return array<int,array<string,mixed>>
 */
function tnt_get_ranked_resource_resource_candidates( $resource ) {

    $resource = $resource instanceof WP_Post ? $resource : get_post( absint( $resource ) );

    if ( ! $resource instanceof WP_Post || 'resource' !== $resource->post_type ) {
        return array();
    }

    $resources = get_posts(
        array(
            'post_type'      => 'resource',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'post__not_in'   => array( $resource->ID ),
            'orderby'        => 'title',
            'order'          => 'ASC',
            'no_found_rows'  => true,
        )
    );

    $ranked = array();

    foreach ( $resources as $candidate ) {
        $result = tnt_calculate_resource_resource_score( $resource, $candidate );

        if ( ! $result ) {
            continue;
        }

        $result['title'] = get_the_title( $candidate );
        $ranked[]        = $result;
    }

    usort(
        $ranked,
        static function ( $left, $right ) {

            if ( $left['score'] !== $right['score'] ) {
                return $right['score'] <=> $left['score'];
            }

            if ( $left['structured_score'] !== $right['structured_score'] ) {
                return $right['structured_score'] <=> $left['structured_score'];
            }

            if ( $left['signals']['tags'] !== $right['signals']['tags'] ) {
                return $right['signals']['tags'] <=> $left['signals']['tags'];
            }

            if ( $left['signals']['topics'] !== $right['signals']['topics'] ) {
                return $right['signals']['topics'] <=> $left['signals']['topics'];
            }

            if ( $left['signals']['title'] !== $right['signals']['title'] ) {
                return $right['signals']['title'] <=> $left['signals']['title'];
            }

            if ( $left['published_timestamp'] !== $right['published_timestamp'] ) {
                return $right['published_timestamp'] <=> $left['published_timestamp'];
            }

            $title_compare = strcasecmp( $left['title'], $right['title'] );

            if ( 0 !== $title_compare ) {
                return $title_compare;
            }

            return $left['candidate_id'] <=> $right['candidate_id'];
        }
    );

    foreach ( $ranked as $index => &$candidate ) {
        $candidate['rank'] = $index + 1;
    }
    unset( $candidate );

    return $ranked;
}

/**
 * Get ranked Resource candidate IDs for editor consumers.
 *
 * @param int $resource_id Current Resource ID.
 * @return int[]
 */
function tnt_get_ranked_resource_resource_candidate_ids( $resource_id ) {

    return array_values(
        array_map(
            static function ( $candidate ) {
                return absint( $candidate['candidate_id'] );
            },
            tnt_get_ranked_resource_resource_candidates( absint( $resource_id ) )
        )
    );
}

/**
 * Get one derived Related Resource score for editor presentation.
 *
 * @param int $resource_id  Current Resource ID.
 * @param int $candidate_id Candidate Resource ID.
 * @return int
 */
function tnt_get_resource_resource_editor_score( $resource_id, $candidate_id ) {

    $result = tnt_calculate_resource_resource_score(
        absint( $resource_id ),
        absint( $candidate_id )
    );

    return $result ? absint( $result['score'] ) : 0;
}
