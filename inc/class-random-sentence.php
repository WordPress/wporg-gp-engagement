<?php
/**
 * This class manages the random sentences to add to the notifications.
 *
 * @package wporg-gp-engagement
 */

namespace WordPressdotorg\GlotPress\Engagement;

/**
 * Manages the random sentences to add to the notifications.
 */
class Random_Sentence {
	/**
	 * The sentences to use.
	 *
	 * @var array
	 */
	private array $sentences = array(
		'Did you know that you can <a href="https://make.wordpress.org/polyglots/handbook/plugin-theme-authors-guide/pte-request/">become a PTE for a project</a>? This means that you’d be allowed to approve translations for that project you care about!',
		'Did you know about the <a href="https://make.wordpress.org/polyglots/handbook/">Translator Handbook</a>? Take a look at them to learn more about the details about contributing translations.',
		'Did you know about <a href="https://make.wordpress.org/polyglots/handbook/translating/glossaries-and-style-guides-per-locale/">glossaries</a>? Your language community has agreed on translating certain words and phrases consistently and you can find those translations there.',
		'Did you know about <a href="https://translate.wordpress.org/events/">Translation Events</a> where contributors translate together? Check out the Translation Events page to find an event you could attend, remotely or in-person.',
		'Did you know you can suggest improvements to translations? Just leave a new translation with your suggestion for others to review!',
		'Did you know there are <a href="https://make.wordpress.org/polyglots/handbook/translating/glossaries-and-style-guides-per-locale/">style guides</a> to keep translations consistent? Your language’s style guide can help you with tone and structure.',
		'Did you know that WordPress translations have validators who review contributions? Every translation goes through a review process to ensure quality.',
		'Did you know that you can help build your language’s <a href="https://make.wordpress.org/polyglots/handbook/translating/glossaries-and-style-guides-per-locale/">glossary</a>? Adding key terms makes translating easier and more consistent.',
		'Did you know that WordPress translations need to stay up-to-date? As features evolve, so do translation needs—your help keeps translations fresh!',
		'Did you know that <a href="https://translate.wordpress.org/">Translate WordPress</a> is always open for contributions? Anytime you’re inspired, your contributions are welcome!',
		'Did you know you can connect with your language’s translation team? Find them in <a href="https://make.wordpress.org/chat/">WordPress’s Slack channels</a> for questions or support.',
		'Did you know that each string in WordPress has a context? Pay attention to context clues to choose the best translation for every situation.',
		'Did you know about <a href="https://make.wordpress.org/polyglots/teams/">locale teams</a>? You can join yours to collaborate with other translators in your language.',
		'Did you know that translators help WordPress reach millions worldwide? Your translations make it accessible for people everywhere!',
	);

	/**
	 * Get a random sentence.
	 *
	 * @return string
	 */
	public function random_string(): string {
		return $this->sentences[ array_rand( $this->sentences ) ];
	}
}
