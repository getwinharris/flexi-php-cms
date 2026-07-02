<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

$failures = [];

function assert_true(bool $condition, string $message): void
{
    global $failures;
    if (!$condition) {
        $failures[] = $message;
    }
}

function assert_same(string $expected, string $actual, string $message): void
{
    global $failures;
    if ($expected !== $actual) {
        $failures[] = $message . "\nExpected: {$expected}\nActual:   {$actual}";
    }
}

function assert_contains(string $needle, string $haystack, string $message): void
{
    global $failures;
    if (strpos($haystack, $needle) === false) {
        $failures[] = $message . "\nMissing: {$needle}\nHTML: {$haystack}";
    }
}

assert_true(is_instagram_url('https://instagram.com/reel/abc'), 'Root instagram.com should be accepted.');
assert_true(is_instagram_url('https://www.instagram.com/reel/abc'), 'www.instagram.com should be accepted.');
assert_true(is_instagram_url('https://m.instagram.com/reel/abc'), 'Mobile Instagram URLs should be accepted.');
assert_true(is_instagram_url('https://l.instagram.com/?u=https%3A%2F%2Finstagram.com%2Freel%2Fabc'), 'Instagram redirector URLs should be accepted.');
assert_true(is_instagram_url('https://foo.instagram.com/reel/abc'), 'Instagram subdomains should be accepted.');
assert_true(!is_instagram_url('https://instagram.net/reel/abc'), 'instagram.net should be rejected.');
assert_true(!is_instagram_url('https://example.com/?next=instagram.com'), 'Non-Instagram hosts should be rejected.');
assert_same('https://www.instagram.com/reel/DFzlzIOqvKv/', canonical_reel_url('https://www.instagram.com/reel/DFzlzIOqvKv/?utm_source=ig_web_button_share_sheet'), 'Instagram Reel tracking URLs should be saved as clean canonical Reel URLs.');
assert_same('https://www.instagram.com/reel/ABC123xyz/media/?size=l', reel_thumbnail_from_url('https://www.instagram.com/reel/ABC123xyz/'), 'Instagram Reel thumbnail should be derived from the Instagram URL.');
assert_same('https://www.instagram.com/reel/MOBILE123/media/?size=l', reel_thumbnail_from_url('https://l.instagram.com/?u=https%3A%2F%2Fwww.instagram.com%2Freel%2FMOBILE123%2F'), 'Instagram redirector thumbnail should use the redirected reel URL.');
assert_same('https://i.ytimg.com/vi/K8ZBQpI09VA/hqdefault.jpg', reel_thumbnail_from_url('https://www.youtube.com/shorts/K8ZBQpI09VA'), 'YouTube Shorts thumbnail should be derived from the video id.');
assert_same('Instagram Reel ABC123xyz', reel_title_from_url('https://www.instagram.com/reel/ABC123xyz/', 4), 'Instagram Reel title should be automatic.');

assert_same('https://cdn.example.com/image.jpg', admin_media_src('https://cdn.example.com/image.jpg'), 'Absolute admin media URLs should not be prefixed.');
assert_same('../assets/uploads/image.jpg', admin_media_src('assets/uploads/image.jpg'), 'Relative admin media URLs should keep the admin parent prefix.');

ob_start();
render_agent_discovery_tags();
$agentTags = ob_get_clean();
assert_contains('href="https://flexifeet.net/llms.txt"', $agentTags, 'Agent discovery tags should point agents to llms.txt.');
assert_contains('href="https://flexifeet.net/mcp.php"', $agentTags, 'Agent discovery tags should expose the MCP endpoint.');

$markdown = <<<MD
# Foot care guide

Intro with **bold care**, *gentle support*, [booking](https://flexifeet.net/#booking), and `scan data`.

## Checklist

- Diabetic shoes
- Offload insoles

1. Book a fitting
2. Bring reports

> Helpful note

![Pressure scan](assets/images/foot-scanning-monitor.png)
MD;

$html = render_post_content($markdown);
assert_contains('<h1>Foot care guide</h1>', $html, 'Markdown H1 should render.');
assert_contains('<strong>bold care</strong>', $html, 'Markdown bold should render.');
assert_contains('<em>gentle support</em>', $html, 'Markdown emphasis should render.');
assert_contains('<a href="https://flexifeet.net/#booking" target="_blank" rel="noopener">booking</a>', $html, 'Markdown links should render safely.');
assert_contains('<code>scan data</code>', $html, 'Inline code should render.');
assert_contains('<ul><li>Diabetic shoes</li><li>Offload insoles</li></ul>', $html, 'Unordered lists should render.');
assert_contains('<ol><li>Book a fitting</li><li>Bring reports</li></ol>', $html, 'Ordered lists should render.');
assert_contains('<blockquote><p>Helpful note</p></blockquote>', $html, 'Blockquotes should render.');
assert_contains('<figure class="blog-inline-image"><img src="assets/images/foot-scanning-monitor.png" alt="Pressure scan"></figure>', $html, 'Markdown images should use site image styling.');

$invalidMailResult = notify_booking_emails([
    'id' => 'QA-MAIL-1',
    'name' => 'QA Mail',
    'email' => '',
]);
assert_same('skipped_mailbox_forwarding', (string) $invalidMailResult['owner'], 'Booking notifier should not send a separate owner email.');
assert_true($invalidMailResult['user'] === false, 'Booking notifier should not send a user email when no valid user address exists.');

$supportModel = flexifeet_support_model();
assert_same('FlexiFeetSupport', (string) $supportModel['name'], 'Local support model should be named FlexiFeetSupport.');
assert_same('raw_files_as_block_weights', (string) $supportModel['training_mode'], 'Local support model should use raw files as block weights.');
assert_true(count($supportModel['documents']) >= 5, 'Local support model should train on multiple project documents.');
assert_true(in_array('flexifeet_project_files', $supportModel['dataset_sources'] ?? [], true), 'Local support model should include Flexi Feet project files as a dataset source.');
assert_true(in_array('raw_repository_byte_blocks', $supportModel['dataset_sources'] ?? [], true), 'Local support model should include raw repository byte blocks.');
assert_true(isset($supportModel['block_index']['block_bytes']) && $supportModel['block_index']['block_bytes'] >= 512, 'Local support model should expose byte block index settings.');
assert_true(count(array_filter($supportModel['documents'], fn($doc) => ($doc['dataset'] ?? '') === 'Raw repository byte block')) >= 10, 'Local support model should index raw repository file byte blocks.');
assert_true(in_array('built_in_multilingual_support_seed', $supportModel['dataset_sources'] ?? [], true) || in_array('storage/support-multilingual-dataset.csv', $supportModel['dataset_sources'] ?? [], true), 'Local support model should include multilingual support patterns.');
assert_true(in_array('built_in_wikidata_entity_seed', $supportModel['dataset_sources'] ?? [], true) || in_array('storage/wikidata-flexifeet.jsonl', $supportModel['dataset_sources'] ?? [], true), 'Local support model should include Wikidata-style entity grounding.');
assert_true(count(array_filter($supportModel['dataset_sources'] ?? [], fn($source) => substr((string) $source, 0, 15) === 'hf_remote_http:')) >= 3 || in_array('hf_remote_http_disabled', $supportModel['dataset_sources'] ?? [], true), 'Local support model should declare Hugging Face remote HTTP dataset sources.');
assert_true(count(array_filter($supportModel['documents'], fn($doc) => isset($doc['dataset']))) >= 5, 'Local support model should include customer-support dataset intent documents.');
assert_true(isset($supportModel['semantic_weights']['diabetic']) || isset($supportModel['semantic_weights']['insole']), 'Local support model should expose learned semantic token weights.');
assert_true(isset($supportModel['file_weights']['homepage']), 'Local support model should expose per-file/document weights.');
assert_true(isset($supportModel['language_model']['bigrams']) && count($supportModel['language_model']['bigrams']) > 20, 'Local support model should learn weighted token transitions from project files.');
assert_true(isset($supportModel['language_model']['trigrams']) && count($supportModel['language_model']['trigrams']) > 20, 'Local support model should learn phrase-level transitions from project files.');
$snippet = flexifeet_support_snippet('Malaysia provides custom footwear and orthopaedic support for diabetic foot care.', 12);
assert_true(str_ends_with($snippet, '.'), 'Support snippets should end cleanly.');
assert_true(strpos($snippet, 'Malaysi.') === false, 'Support snippets should not cut words in the middle.');
$semanticReply = support_bot_reply('Do you provide diabetic shoes and 3D foot scanning in Sentul?');
assert_same('FlexiFeetSupport', (string) ($semanticReply['model'] ?? ''), 'Support replies should come from the local FlexiFeetSupport model.');
assert_same('service', (string) ($semanticReply['intent'] ?? ''), 'Service questions should be classified as service intent.');
assert_true(stripos((string) ($semanticReply['reply'] ?? ''), 'Flexi Feet') !== false, 'Support replies should mention Flexi Feet.');
assert_true((string) ($semanticReply['response_id'] ?? '') !== '', 'Support replies should include a response id for feedback.');
assert_true(!array_key_exists('raw', $semanticReply), 'Support replies should not expose remote AI raw output.');
assert_true(isset($semanticReply['learned_terms']) && is_array($semanticReply['learned_terms']), 'Support replies should expose local model learned terms.');
$suggestionUrls = array_map(fn($item) => $item['url'] ?? '', $semanticReply['suggestions'] ?? []);
assert_true(count($suggestionUrls) === count(array_unique($suggestionUrls)), 'Support suggestions should not contain duplicate URLs.');
$greetingReply = support_bot_reply('hello');
assert_same('greeting', (string) ($greetingReply['intent'] ?? ''), 'Generic hello prompts should get a greeting/support entry response.');
assert_true(stripos((string) ($greetingReply['reply'] ?? ''), 'diabetic shoes') !== false, 'Greeting response should explain what the support agent can help with.');
$bookingReply = support_bot_reply('I want to book an appointment');
assert_same('booking', (string) ($bookingReply['intent'] ?? ''), 'Booking prompts should start booking intent.');
assert_true(stripos((string) ($bookingReply['reply'] ?? ''), 'step by step') !== false, 'Booking prompts should start a guided chat, not just show a form.');
assert_true(stripos((string) ($bookingReply['reply'] ?? ''), 'what is the booking for') !== false, 'Booking prompts should ask for the first required field.');
$malayReply = support_bot_reply('Saya mahu kasut diabetes dan temujanji imbasan kaki 3D');
assert_same('ms', (string) ($malayReply['language'] ?? ''), 'Malay support queries should be detected.');
assert_true(stripos((string) ($malayReply['reply'] ?? ''), 'Flexi Feet') !== false, 'Malay support replies should remain grounded in Flexi Feet.');
assert_same('ta', flexifeet_support_detect_language('நீரிழிவு காலணி கிடைக்குமா'), 'Tamil support queries should be detected.');
assert_same('zh', flexifeet_support_detect_language('我想预约糖尿病鞋'), 'Chinese support queries should be detected.');
$unicodeTokens = flexifeet_support_tokenize('கால் 扫描 kasut diabetes');
assert_true(in_array('கால்', $unicodeTokens, true) || in_array('扫描', $unicodeTokens, true) || in_array('kasut', $unicodeTokens, true), 'Support tokenizer should keep multilingual tokens.');
$offTopicReply = support_bot_reply('Can you help me trade cryptocurrency?');
assert_same('FlexiFeetSupport', (string) ($offTopicReply['model'] ?? ''), 'Out-of-scope replies should also be local model replies.');
assert_same('out_of_scope', (string) ($offTopicReply['intent'] ?? ''), 'Off-topic messages should stay out of scope.');
$randomReply = support_bot_reply('purple spaceship mango keyboard');
assert_same('out_of_scope', (string) ($randomReply['intent'] ?? ''), 'Random unrelated prompts should stay out of scope.');
$policyReply = support_bot_reply('What is the return policy and delivery time for custom diabetic shoes?');
assert_same('service', (string) ($policyReply['intent'] ?? ''), 'Policy and delivery prompts should reason from support datasets.');
assert_true(stripos((string) ($policyReply['reply'] ?? ''), 'Flexi Feet') !== false, 'Policy and delivery replies should stay grounded in Flexi Feet.');
assert_true(stripos((string) ($policyReply['reply'] ?? ''), '3 to 4 weeks') !== false, 'Policy and delivery replies should include delivery timing.');
assert_true(stripos((string) ($policyReply['reply'] ?? ''), 'change of mind') !== false, 'Policy and delivery replies should include custom return policy.');
$servicesReply = support_bot_reply('What services do you offer?');
assert_same('service', (string) ($servicesReply['intent'] ?? ''), 'Generic service prompts should stay in scope.');
assert_true(stripos((string) ($servicesReply['reply'] ?? ''), '3D foot scanning') !== false, 'Generic service prompts should explain actual services.');
$locationReply = support_bot_reply('Where is your shop?');
assert_same('service', (string) ($locationReply['intent'] ?? ''), 'Location prompts should stay in scope.');
assert_true(stripos((string) ($locationReply['reply'] ?? ''), 'Residency Awani') !== false, 'Location prompts should include the shop address.');
$hoursReply = support_bot_reply('Are you open on Sunday?');
assert_same('service', (string) ($hoursReply['intent'] ?? ''), 'Hours prompts should stay in scope.');
assert_true(stripos((string) ($hoursReply['reply'] ?? ''), 'Sunday is closed') !== false, 'Sunday prompts should mention Sunday closure.');

$hadFeedbackFile = is_file(SUPPORT_FEEDBACK_FILE);
$hadTttFile = is_file(SUPPORT_TTT_MEMORY_FILE);
$originalFeedbackJson = $hadFeedbackFile ? (string) file_get_contents(SUPPORT_FEEDBACK_FILE) : '';
$originalTttJson = $hadTttFile ? (string) file_get_contents(SUPPORT_TTT_MEMORY_FILE) : '';
try {
    $feedbackResult = create_support_feedback([
        'response_id' => $semanticReply['response_id'] ?? 'TEST',
        'rating' => 'like',
        'intent' => 'service',
        'language' => 'en',
        'message' => 'Do you provide diabetic shoes and 3D foot scanning in Sentul?',
    ]);
    assert_true($feedbackResult['ok'] === true, 'Support feedback should be saved.');
    assert_true(count(read_support_feedback()) >= 1, 'Support feedback storage should contain the saved signal.');
    assert_true(count(read_support_ttt_documents()) >= 1, 'Liked feedback should create local TTT memory.');
} finally {
    if ($hadFeedbackFile) {
        file_put_contents(SUPPORT_FEEDBACK_FILE, $originalFeedbackJson, LOCK_EX);
    } elseif (is_file(SUPPORT_FEEDBACK_FILE)) {
        unlink(SUPPORT_FEEDBACK_FILE);
    }
    if ($hadTttFile) {
        file_put_contents(SUPPORT_TTT_MEMORY_FILE, $originalTttJson, LOCK_EX);
    } elseif (is_file(SUPPORT_TTT_MEMORY_FILE)) {
        unlink(SUPPORT_TTT_MEMORY_FILE);
    }
}

$originalPostsJson = is_file(BLOG_POSTS_FILE) ? (string) file_get_contents(BLOG_POSTS_FILE) : '';
try {
    $post = save_blog_post([
        'title' => 'QA Slug Fallback Post',
        'slug' => '',
        'excerpt' => 'Short excerpt',
        'content' => str_repeat('Useful content. ', 40),
        'status' => 'Draft',
    ]);
    assert_same('qa-slug-fallback-post', $post['slug'], 'Empty blog slug should fall back to the title.');

    $updated = save_blog_post([
        'title' => 'QA Slug Fallback Post',
        'slug' => $post['slug'],
        'excerpt' => 'Updated excerpt',
        'content' => str_repeat('Useful content. ', 40),
        'status' => 'Draft',
    ], $post['id']);
    $matches = array_values(array_filter(read_blog_posts(false), fn($item) => ($item['id'] ?? '') === $post['id']));
    assert_true(count($matches) === 1, 'Updating an existing post id should not create a duplicate.');
    assert_same($post['id'], $updated['id'], 'Updating a post should preserve its id.');
} finally {
    file_put_contents(BLOG_POSTS_FILE, $originalPostsJson, LOCK_EX);
}

$originalReelsJson = is_file(REELS_FILE) ? (string) file_get_contents(REELS_FILE) : '';
try {
    $reel = save_reel([
        'url' => 'https://www.instagram.com/reel/ABC123xyz/?utm_source=ig_web_button_share_sheet',
        'status' => 'Active',
    ]);
    assert_same('https://www.instagram.com/reel/ABC123xyz/', $reel['url'], 'Saving a reel should store the clean canonical URL.');
    assert_same('Instagram Reel ABC123xyz', $reel['title'], 'Saving a reel should generate the title from the URL.');
    assert_same('https://www.instagram.com/reel/ABC123xyz/media/?size=l', $reel['thumbnail'], 'Saving a reel should generate the thumbnail from the URL.');
    assert_true((int) $reel['sort_order'] >= 1, 'Saving a reel should set a positive sort order.');
} finally {
    file_put_contents(REELS_FILE, $originalReelsJson, LOCK_EX);
}

if (!empty($failures)) {
    fwrite(STDERR, implode("\n\n", $failures) . "\n");
    exit(1);
}

echo "All tests passed.\n";
