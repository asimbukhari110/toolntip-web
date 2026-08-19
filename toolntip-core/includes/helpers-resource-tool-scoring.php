<?php
/**
 * Resource -> Tool relationship scoring.
 *
 * WEB-007.4 / 4.2A-C
 *
 * Scores are derived editorial intelligence. They are never persisted as the
 * canonical relationship. Final Related Tool relationships remain ordered
 * Tool IDs selected by the editor.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Frozen scoring weights for Resource -> Tool recommendations.
 *
 * The total is intentionally normalized to 100.
 *
 * @return array<string,int>
 */
function tnt_resource_tool_scoring_weights() {
    return array(
        'resource_tag' => 30,
        'topic'        => 25,
        'title'        => 20,
        'feature'      => 15,
        'content'      => 10,
    );
}

/**
 * Frozen minimum score at which a candidate is considered qualified.
 *
 * Automatic preselection is implemented separately in 4.2A-E.
 *
 * @return int
 */
function tnt_resource_tool_qualification_threshold() {
    return 55;
}

/**
 * Normalize arbitrary text into meaningful lowercase tokens.
 *
 * @param mixed $value Raw text/value.
 * @return string[]
 */
function tnt_resource_scoring_tokens( $value ) {

    if ( is_array( $value ) ) {
        $value = implode( ' ', array_map( 'strval', $value ) );
    }

    $value = html_entity_decode( wp_strip_all_tags( (string) $value ), ENT_QUOTES, 'UTF-8' );
    $value = strtolower( remove_accents( $value ) );

    // Keep letters/numbers and normalize all other separators to spaces.
    $value = preg_replace( '/[^\p{L}\p{N}]+/u', ' ', $value );

    if ( ! is_string( $value ) || '' === trim( $value ) ) {
        return array();
    }

    $stopwords = array(
        'a', 'an', 'and', 'are', 'as', 'at', 'be', 'by', 'for', 'from',
        'how', 'in', 'is', 'it', 'of', 'on', 'or', 'that', 'the', 'this',
        'to', 'with', 'your', 'online', 'tool', 'tools',
    );

    $tokens = preg_split( '/\s+/u', trim( $value ) );
    $tokens = array_filter(
        array_map( 'trim', (array) $tokens ),
        static function ( $token ) use ( $stopwords ) {
            return strlen( $token ) >= 2 && ! in_array( $token, $stopwords, true );
        }
    );

    return array_values( array_unique( $tokens ) );
}

/**
 * Remove broad cross-domain vocabulary from structured semantic signals.
 *
 * These words remain available to title/content similarity where context can
 * make them meaningful, but they cannot independently manufacture taxonomy/
 * feature relevance. This keeps distinctive concepts (json, base64, encoder,
 * formatter, validator, etc.) as the primary structured evidence.
 *
 * @param string[] $tokens Tokens to filter.
 * @return string[]
 */
function tnt_resource_scoring_distinctive_tokens( $tokens ) {

    $semantic_noise = array(
        'app',
        'application',
        'browser',
        'code',
        'coding',
        'developer',
        'development',
        'editor',
        'editors',
        'free',
        'internal',
        'external',
        'software',
        'utility',
        'utilities',
        'web',
        'website',
    );

    $tokens = array_values( array_unique( array_filter( (array) $tokens ) ) );

    return array_values(
        array_filter(
            $tokens,
            static function ( $token ) use ( $semantic_noise ) {
                return ! in_array( $token, $semantic_noise, true );
            }
        )
    );
}

/**
 * Get normalized semantic tokens from taxonomy terms.
 *
 * Both names and slugs are included because editorial vocabularies may use
 * spaces while their canonical slugs use hyphens.
 *
 * @param int    $post_id  Object ID.
 * @param string $taxonomy Taxonomy name.
 * @return string[]
 */
function tnt_resource_scoring_taxonomy_tokens( $post_id, $taxonomy ) {

    if ( ! taxonomy_exists( $taxonomy ) ) {
        return array();
    }

    $terms = wp_get_post_terms( absint( $post_id ), $taxonomy );

    if ( is_wp_error( $terms ) || empty( $terms ) ) {
        return array();
    }

    $tokens = array();

    foreach ( $terms as $term ) {
        $tokens = array_merge(
            $tokens,
            tnt_resource_scoring_tokens( $term->name ),
            tnt_resource_scoring_tokens( str_replace( '-', ' ', $term->slug ) )
        );
    }

    return array_values( array_unique( $tokens ) );
}

/**
 * Return a 0..1 overlap ratio from source tokens to candidate tokens.
 *
 * Source-oriented coverage is intentional: if a Resource has two controlled
 * semantic tags and one matches a Tool, that is stronger than one match among
 * ten Resource tags.
 *
 * @param string[] $source_tokens    Source tokens.
 * @param string[] $candidate_tokens Candidate tokens.
 * @return float
 */
function tnt_resource_scoring_coverage( $source_tokens, $candidate_tokens ) {

    $source_tokens    = array_values( array_unique( array_filter( (array) $source_tokens ) ) );
    $candidate_tokens = array_values( array_unique( array_filter( (array) $candidate_tokens ) ) );

    if ( empty( $source_tokens ) || empty( $candidate_tokens ) ) {
        return 0.0;
    }

    $matches = count( array_intersect( $source_tokens, $candidate_tokens ) );

    if ( 0 === $matches ) {
        return 0.0;
    }

    /*
     * WEB-007.4 / 4.2A-C.1
     *
     * Evidence accumulation must be monotonic: adding a genuinely matching
     * Resource term must never reduce a signal that was already earned.
     *
     * One unique semantic match establishes relevance; additional unique
     * matches strengthen confidence until the signal reaches full weight.
     * The denominator is therefore a fixed evidence target, not the mutable
     * number of Resource terms.
     */
    $evidence_target = 3;

    return min( 1.0, $matches / $evidence_target );
}

/**
 * Return a symmetric 0..1 token similarity.
 *
 * @param string[] $left  Left tokens.
 * @param string[] $right Right tokens.
 * @return float
 */
function tnt_resource_scoring_similarity( $left, $right ) {

    $left  = array_values( array_unique( array_filter( (array) $left ) ) );
    $right = array_values( array_unique( array_filter( (array) $right ) ) );

    if ( empty( $left ) || empty( $right ) ) {
        return 0.0;
    }

    $intersection = count( array_intersect( $left, $right ) );
    $union        = count( array_unique( array_merge( $left, $right ) ) );

    return $union > 0 ? min( 1.0, $intersection / $union ) : 0.0;
}

/**
 * Calculate one weighted signal contribution.
 *
 * @param float $ratio 0..1 ratio.
 * @param int   $max   Signal maximum.
 * @return int
 */
function tnt_resource_scoring_weighted_value( $ratio, $max ) {
    return max( 0, min( absint( $max ), (int) round( max( 0.0, min( 1.0, (float) $ratio ) ) * absint( $max ) ) ) );
}

/**
 * Build Resource semantic data once per scoring pass.
 *
 * Resource Type is retained as contextual editorial data in the returned
 * structure. The frozen cross-domain Tool score does not award points merely
 * because a Resource is an Article/Tutorial/How-To; those types do not have a
 * truthful one-to-one mapping to internal/external Tool types.
 *
 * @param WP_Post $resource Resource post.
 * @return array<string,mixed>
 */
function tnt_get_resource_tool_scoring_context( WP_Post $resource ) {

    $title_tokens   = tnt_resource_scoring_tokens( get_the_title( $resource ) );
    $excerpt_tokens = tnt_resource_scoring_tokens( get_the_excerpt( $resource ) );
    $content_tokens = tnt_resource_scoring_tokens( $resource->post_content );

    $tag_tokens   = tnt_resource_scoring_taxonomy_tokens( $resource->ID, 'resource_tag' );

    /*
     * WEB-007.4 / 4.2A-M1
     * Resource Topics and Tool Categories now share the canonical
     * tool_category taxonomy. The scoring context must therefore read both
     * the topic vocabulary and canonical term IDs from tool_category.
     */
    $topic_tokens = tnt_resource_scoring_taxonomy_tokens( $resource->ID, 'tool_category' );
    $type_tokens  = tnt_resource_scoring_taxonomy_tokens( $resource->ID, 'resource_type' );

    $category_ids = wp_get_object_terms(
        $resource->ID,
        'tool_category',
        array( 'fields' => 'ids' )
    );

    if ( is_wp_error( $category_ids ) ) {
        $category_ids = array();
    }

    $category_ids = array_values(
        array_filter(
            array_map( 'absint', (array) $category_ids )
        )
    );

    return array(
        'resource_id'     => $resource->ID,
        'category_ids'    => $category_ids,
        'title_tokens'    => $title_tokens,
        'excerpt_tokens'  => $excerpt_tokens,
        'content_tokens'  => $content_tokens,
        'tag_tokens'      => $tag_tokens,
        'topic_tokens'    => $topic_tokens,
        'type_tokens'     => $type_tokens,
        'semantic_tokens' => array_values(
            array_unique(
                array_merge(
                    $tag_tokens,
                    $topic_tokens,
                    $title_tokens,
                    $excerpt_tokens,
                    $content_tokens
                )
            )
        ),
    );
}

/**
 * Build candidate Tool semantic data.
 *
 * @param WP_Post $tool Candidate Tool.
 * @return array<string,mixed>
 */
function tnt_get_resource_tool_candidate_context( WP_Post $tool ) {

    $title_tokens = tnt_resource_scoring_tokens( get_the_title( $tool ) );

    $tag_tokens      = tnt_resource_scoring_taxonomy_tokens( $tool->ID, 'tool_tag' );
    $category_tokens = tnt_resource_scoring_taxonomy_tokens( $tool->ID, 'tool_category' );
    $feature_tax_tokens = tnt_resource_scoring_taxonomy_tokens( $tool->ID, 'tool_feature' );

    $tagline = function_exists( 'get_field' ) ? get_field( 'tool_tagline', $tool->ID ) : '';
    $about   = function_exists( 'get_field' ) ? get_field( 'about_this_tool', $tool->ID ) : '';

    $feature_values = function_exists( 'tnt_get_tool_features' )
        ? tnt_get_tool_features( $tool )
        : array();

    $feature_tokens = array_values(
        array_unique(
            array_merge(
                $feature_tax_tokens,
                tnt_resource_scoring_tokens( $feature_values )
            )
        )
    );

    $content_tokens = array_values(
        array_unique(
            array_merge(
                $title_tokens,
                tnt_resource_scoring_tokens( get_the_excerpt( $tool ) ),
                tnt_resource_scoring_tokens( $tagline ),
                tnt_resource_scoring_tokens( $about )
            )
        )
    );

    $category_ids = wp_get_object_terms(
        $tool->ID,
        'tool_category',
        array( 'fields' => 'ids' )
    );
    $category_ids = is_wp_error( $category_ids ) ? array() : array_values( array_map( 'absint', $category_ids ) );

    return array(
        'tool_id'          => $tool->ID,
        'category_ids'     => $category_ids,
        'title_tokens'     => $title_tokens,
        'tag_tokens'       => $tag_tokens,
        'category_tokens'  => $category_tokens,
        'feature_tokens'   => $feature_tokens,
        'content_tokens'   => $content_tokens,
    );
}

/**
 * Calculate frozen Resource -> Tool relevance score.
 *
 * @param WP_Post|int $resource  Resource object or ID.
 * @param WP_Post|int $candidate Candidate Tool object or ID.
 * @return array<string,mixed>|null Structured score result.
 */
function tnt_calculate_resource_tool_score( $resource, $candidate ) {

    $resource = $resource instanceof WP_Post ? $resource : get_post( absint( $resource ) );
    $candidate = $candidate instanceof WP_Post ? $candidate : get_post( absint( $candidate ) );

    if (
        ! $resource instanceof WP_Post
        || 'resource' !== $resource->post_type
        || ! $candidate instanceof WP_Post
        || 'tool' !== $candidate->post_type
        || 'publish' !== $candidate->post_status
    ) {
        return null;
    }

    $weights  = tnt_resource_tool_scoring_weights();
    $resource_context = tnt_get_resource_tool_scoring_context( $resource );
    $tool_context     = tnt_get_resource_tool_candidate_context( $candidate );

    /*
     * 1. Resource Tag -> Tool Tag semantic match (max 30).
     */
    $tag_score = tnt_resource_scoring_weighted_value(
        tnt_resource_scoring_coverage(
            tnt_resource_scoring_distinctive_tokens( $resource_context['tag_tokens'] ),
            tnt_resource_scoring_distinctive_tokens(
                array_values(
                    array_unique(
                        array_merge(
                            $tool_context['tag_tokens'],
                            $tool_context['category_tokens'],
                            $tool_context['title_tokens'],
                            $tool_context['feature_tokens']
                        )
                    )
                )
            )
        ),
        $weights['resource_tag']
    );

    /*
     * 2. Resource Topic -> Tool Category canonical taxonomy match (max 25).
     *
     * Resource Topics and Tool Categories now share the same tool_category
     * taxonomy. At least one shared canonical term ID establishes the full
     * structured domain signal. No string/semantic approximation is used.
     */
    $shared_category_ids = array_intersect(
        $resource_context['category_ids'],
        $tool_context['category_ids']
    );

    $topic_score = ! empty( $shared_category_ids )
        ? (float) $weights['topic']
        : 0.0;

    /*
     * 3. Resource title -> Tool title similarity (max 20).
     */
    $title_score = tnt_resource_scoring_weighted_value(
        tnt_resource_scoring_similarity(
            $resource_context['title_tokens'],
            $tool_context['title_tokens']
        ),
        $weights['title']
    );

    /*
     * 4. Resource semantic vocabulary -> Tool feature vocabulary (max 15).
     */
    $feature_score = tnt_resource_scoring_weighted_value(
        tnt_resource_scoring_coverage(
            tnt_resource_scoring_distinctive_tokens(
                array_values(
                    array_unique(
                        array_merge(
                            $resource_context['tag_tokens'],
                            $resource_context['topic_tokens'],
                            $resource_context['title_tokens']
                        )
                    )
                )
            ),
            tnt_resource_scoring_distinctive_tokens( $tool_context['feature_tokens'] )
        ),
        $weights['feature']
    );

    /*
     * 5. Broader Resource content/excerpt -> Tool editorial corpus (max 10).
     */
    $content_score = tnt_resource_scoring_weighted_value(
        tnt_resource_scoring_similarity(
            array_values(
                array_unique(
                    array_merge(
                        $resource_context['title_tokens'],
                        $resource_context['excerpt_tokens'],
                        $resource_context['content_tokens']
                    )
                )
            ),
            $tool_context['content_tokens']
        ),
        $weights['content']
    );

    $signals = array(
        'resource_tag' => $tag_score,
        'topic'        => $topic_score,
        'title'        => $title_score,
        'feature'      => $feature_score,
        'content'      => $content_score,
    );

    $score = max( 0, min( 100, array_sum( $signals ) ) );

    // Structured subtotal is used as the first deterministic tie-breaker.
    $structured_score = $tag_score + $topic_score + $feature_score;

    return array(
        'resource_id'      => $resource->ID,
        'candidate_id'     => $candidate->ID,
        'candidate_type'   => 'tool',
        'score'            => $score,
        'qualified'        => $score >= tnt_resource_tool_qualification_threshold(),
        'structured_score' => $structured_score,
        'signals'          => $signals,
    );
}

/**
 * Get all published Tool candidates ranked for a Resource.
 *
 * Frozen ordering:
 * 1. final score DESC
 * 2. structured semantic subtotal DESC
 * 3. Tool title ASC
 * 4. Tool ID ASC
 *
 * @param WP_Post|int $resource Resource object or ID.
 * @return array<int,array<string,mixed>>
 */
function tnt_get_ranked_resource_tool_candidates( $resource ) {

    $resource = $resource instanceof WP_Post ? $resource : get_post( absint( $resource ) );

    if ( ! $resource instanceof WP_Post || 'resource' !== $resource->post_type ) {
        return array();
    }

    $tools = get_posts(
        array(
            'post_type'      => 'tool',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'no_found_rows'  => true,
        )
    );

    $ranked = array();

    foreach ( $tools as $tool ) {
        $result = tnt_calculate_resource_tool_score( $resource, $tool );

        if ( ! $result ) {
            continue;
        }

        $result['title'] = get_the_title( $tool );
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
 * Get ranked Tool candidate IDs for ACF/editor consumers.
 *
 * @param int $resource_id Resource ID.
 * @return int[]
 */
function tnt_get_ranked_resource_tool_candidate_ids( $resource_id ) {

    return array_values(
        array_map(
            static function ( $candidate ) {
                return absint( $candidate['candidate_id'] );
            },
            tnt_get_ranked_resource_tool_candidates( absint( $resource_id ) )
        )
    );
}

/**
 * Get one derived Tool score for editor presentation.
 *
 * @param int $resource_id Resource ID.
 * @param int $tool_id     Tool ID.
 * @return int
 */
function tnt_get_resource_tool_editor_score( $resource_id, $tool_id ) {

    $result = tnt_calculate_resource_tool_score(
        absint( $resource_id ),
        absint( $tool_id )
    );

    return $result ? absint( $result['score'] ) : 0;
}
