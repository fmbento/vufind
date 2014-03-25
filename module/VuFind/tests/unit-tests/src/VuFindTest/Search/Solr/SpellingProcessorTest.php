<?php

/**
 * Unit tests for spelling processor.
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2013.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind2
 * @package  Search
 * @author   David Maus <maus@hab.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */

namespace VuFindTest\Search\Solr;

use VuFind\Search\Solr\SpellingProcessor;
use VuFindTest\Unit\TestCase;
use Zend\Config\Config;

/**
 * Unit tests for spelling processor.
 *
 * @category VuFind2
 * @package  Search
 * @author   David Maus <maus@hab.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
class SpellingProcessorTest extends TestCase
{
    /**
     * Test defaults.
     *
     * @return void
     */
    public function testDefaultConfigs()
    {
        $sp = new SpellingProcessor();
        $this->assertEquals(true, $sp->shouldSkipNumericSpelling());
        $this->assertEquals(3, $sp->getSpellingLimit());
    }

    /**
     * Test non-default configs.
     *
     * @return void
     */
    public function testNonDefaultConfigs()
    {
        $config = new Config(array('limit' => 5, 'skip_numeric' => false));
        $sp = new SpellingProcessor($config);
        $this->assertEquals(false, $sp->shouldSkipNumericSpelling());
        $this->assertEquals(5, $sp->getSpellingLimit());
    }

    /**
     * Test suggestion processing.
     *
     * @return void
     */
    public function testSuggestionProcessing()
    {
        $sp = new SpellingProcessor();
        $spelling = $this->getFixture('spell1');
        $query = $this->getFixture('query1');
        $this->assertEquals(
            $this->getExpectedQuery1Suggestions(),
            $sp->getSuggestions($spelling, $query)
        );
    }

    /**
     * Test suggestion processing.
     *
     * @return void
     */
    public function testSuggestionProcessingWithNonDefaultLimit()
    {
        $config = new Config(array('limit' => 5));
        $sp = new SpellingProcessor($config);
        $spelling = $this->getFixture('spell1');
        $query = $this->getFixture('query1');
        $this->assertEquals(
            array(
                'grumble' => array(
                    'freq' => 2,
                    'suggestions' => array(
                        'grumbler' => 4,
                        'rumble' => 40,
                        'crumble' => 15,
                        'trumble' => 13,
                        'brumble' => 3,
                    ),
                ),
                'grimble' => array(
                    'freq' => 7,
                    'suggestions' => array(
                        'trimble' => 110,
                        'gribble' => 21,
                        'grimsley' => 24,
                        'grimalde' => 8,
                    ),
                ),
            ),
            $sp->getSuggestions($spelling, $query)
        );
    }

    /**
     * Test basic suggestions.
     *
     * @return void
     */
    public function testBasicSuggestions()
    {
        $spelling = $this->getFixture('spell1');
        $query = $this->getFixture('query1');
        $params = $this->getServiceManager()->get('VuFind\SearchParamsPluginManager')
            ->get('Solr');
        $params->setBasicSearch($query->getString(), $query->getHandler());
        $sp = new SpellingProcessor();
        $this->assertEquals(
            array(
                'grumble' => array(
                    'freq' => 2,
                    'suggestions' => array(
                        'grumbler' => array(
                            'freq' => 4,
                            'new_term' => 'grumbler',
                            'expand_term' => '(grumble OR grumbler)',
                        ),
                        'rumble' => array(
                            'freq' => 40,
                            'new_term' => 'rumble',
                            'expand_term' => '(grumble OR rumble)',
                        ),
                        'crumble' => array(
                            'freq' => 15,
                            'new_term' => 'crumble',
                            'expand_term' => '(grumble OR crumble)',
                        ),
                    ),
                ),
                'grimble' => array(
                    'freq' => 7,
                    'suggestions' => array(
                        'trimble' => array(
                            'freq' => 110,
                            'new_term' => 'trimble',
                            'expand_term' => '(grimble OR trimble)',
                        ),
                        'gribble' => array(
                            'freq' => 21,
                            'new_term' => 'gribble',
                            'expand_term' => '(grimble OR gribble)',
                        ),
                        'grimsley' => array(
                            'freq' => 24,
                            'new_term' => 'grimsley',
                            'expand_term' => '(grimble OR grimsley)',
                        ),
                    ),
                ),
            ),
            $sp->processSuggestions(
                $this->getExpectedQuery1Suggestions(), $spelling->getQuery(), $params
            )
        );
    }

    /**
     * Test basic suggestions with expansions disabled and phrase display on.
     *
     * @return void
     */
    public function testBasicSuggestionsWithNonDefaultSettings()
    {
        $spelling = $this->getFixture('spell1');
        $query = $this->getFixture('query1');
        $params = $this->getServiceManager()->get('VuFind\SearchParamsPluginManager')
            ->get('Solr');
        $params->setBasicSearch($query->getString(), $query->getHandler());
        $config = new Config(array('expand' => false, 'phrase' => true));
        $sp = new SpellingProcessor($config);
        $this->assertEquals(
            array(
                'grumble' => array(
                    'freq' => 2,
                    'suggestions' => array(
                        'grumbler grimble' => array(
                            'freq' => 4,
                            'new_term' => 'grumbler',
                        ),
                        'rumble grimble' => array(
                            'freq' => 40,
                            'new_term' => 'rumble',
                        ),
                        'crumble grimble' => array(
                            'freq' => 15,
                            'new_term' => 'crumble',
                        ),
                    ),
                ),
                'grimble' => array(
                    'freq' => 7,
                    'suggestions' => array(
                        'grumble trimble' => array(
                            'freq' => 110,
                            'new_term' => 'trimble',
                        ),
                        'grumble gribble' => array(
                            'freq' => 21,
                            'new_term' => 'gribble',
                        ),
                        'grumble grimsley' => array(
                            'freq' => 24,
                            'new_term' => 'grimsley',
                        ),
                    ),
                ),
            ),
            $sp->processSuggestions(
                $this->getExpectedQuery1Suggestions(), $spelling->getQuery(), $params
            )
        );
    }

    /**
     * Test that spelling tokenization works correctly.
     *
     * @return void
     */
    public function testSpellingTokenization()
    {
        $sp = new SpellingProcessor();
        $this->assertEquals(array('single'), $sp->tokenize('single'));
        $this->assertEquals(array('two', 'terms'), $sp->tokenize('two terms'));
        $this->assertEquals(array('two', 'terms'), $sp->tokenize('two    terms'));
        $this->assertEquals(array('apples', 'oranges'), $sp->tokenize('apples OR oranges'));
        $this->assertEquals(array('"word"'), $sp->tokenize('"word"'));
        $this->assertEquals(array('"word"', 'second'), $sp->tokenize('"word" second'));
        $this->assertEquals(array(), $sp->tokenize(''));
        $this->assertEquals(array('0', 'is', 'zero'), $sp->tokenize('0 is zero'));
        $this->assertEquals(array("'twas", 'successful'), $sp->tokenize("'twas successful"));
        $this->assertEquals(array('word'), $sp->tokenize('(word)'));
        $this->assertEquals(array('word', 'second'), $sp->tokenize('(word) second'));
        $this->assertEquals(array('apples', 'oranges', 'pears'), $sp->tokenize('(apples OR oranges) AND pears'));
        $this->assertEquals(array('two', 'terms'), $sp->tokenize("two\tterms"));
        $this->assertEquals(
            array('"two words"', 'single', '"three word phrase"', 'single'),
            $sp->tokenize('((("two words" OR single) NOT "three word phrase") AND single)')
        );
        $this->assertEquals(array('"unfinished phrase'), $sp->tokenize('"unfinished phrase'));
        $this->assertEquals(array('"'), $sp->tokenize('"'));
        $this->assertEquals(array('""'), $sp->tokenize('""'));
    }

    /**
     * Get expected suggestions for the "query1" example.
     *
     * @return array
     */
    protected function getExpectedQuery1Suggestions()
    {
        return array(
            'grumble' => array(
                'freq' => 2,
                'suggestions' => array(
                    'grumbler' => 4,
                    'rumble' => 40,
                    'crumble' => 15,
                ),
            ),
            'grimble' => array(
                'freq' => 7,
                'suggestions' => array(
                    'trimble' => 110,
                    'gribble' => 21,
                    'grimsley' => 24
                ),
            ),
        );
    }

    /**
     * Get a fixture object
     *
     * @return mixed
     */
    protected function getFixture($file)
    {
        $fixturePath = realpath(__DIR__ . '/../../../../../fixtures/spell') . '/';
        return unserialize(file_get_contents($fixturePath . $file));
    }
}
