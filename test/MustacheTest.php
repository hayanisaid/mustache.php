<?php

require_once '../Mustache.php';
require_once 'PHPUnit/Framework.php';

/**
 * A PHPUnit test case for Mustache.php.
 *
 * This is a very basic, very rudimentary unit test case. It's probably more important to have tests
 * than to have elegant tests, so let's bear with it for a bit.
 *
 * This class assumes an example directory exists at `../examples` with the following structure:
 *
 * @code
 *    examples
 *        foo
 *            Foo.php
 *            foo.mustache
 *            foo.txt
 *        bar
 *            Bar.php
 *            bar.mustache
 *            bar.txt
 * @endcode
 *
 * To use this test:
 *
 *  1. {@link http://www.phpunit.de/manual/current/en/installation.html Install PHPUnit}
 *  2. run phpunit from the `test` directory:
 *        `phpunit MustacheTest`
 *  3. Fix bugs. Lather, rinse, repeat.
 *
 * @extends PHPUnit_Framework_TestCase
 */
class MustacheTest extends PHPUnit_Framework_TestCase {

	const TEST_CLASS = 'Mustache';

	/**
	 * Test Mustache constructor.
	 *
	 * @access public
	 * @return void
	 */
	public function test__construct() {
		$template = '{{#mustaches}}{{#last}}and {{/last}}{{type}}{{^last}}, {{/last}}{{/mustaches}}';
		$data     = array(
			'mustaches' => array(
				array('type' => 'Natural'),
				array('type' => 'Hungarian'),
				array('type' => 'Dali'),
				array('type' => 'English'),
				array('type' => 'Imperial'),
				array('type' => 'Freestyle', 'last' => 'true'),
			)
		);
		$output = 'Natural, Hungarian, Dali, English, Imperial, and Freestyle';

		$m1 = new Mustache();
		$this->assertEquals($output, $m1->render($template, $data));

		$m2 = new Mustache($template);
		$this->assertEquals($output, $m2->render(null, $data));

		$m3 = new Mustache($template, $data);
		$this->assertEquals($output, $m3->render());

		$m4 = new Mustache(null, $data);
		$this->assertEquals($output, $m4->render($template));
	}

	/**
	 * Test __toString() function.
	 *
	 * @access public
	 * @return void
	 */
	public function test__toString() {
		$m = new Mustache('{{first_name}} {{last_name}}', array('first_name' => 'Karl', 'last_name' => 'Marx'));

		$this->assertEquals('Karl Marx', $m->__toString());
		$this->assertEquals('Karl Marx', (string) $m);

		$m2 = $this->getMock(self::TEST_CLASS, array('render'), array());
		$m2->expects($this->once())
			->method('render')
			->will($this->returnValue('foo'));

		$this->assertEquals('foo', $m2->render());
	}

	public function test__toStringException() {
		$m = $this->getMock(self::TEST_CLASS, array('render'), array());
		$m->expects($this->once())
			->method('render')
			->will($this->throwException(new Exception));

		try {
			$out = (string) $m;
		} catch (Exception $e) {
			$this->fail('__toString should catch all exceptions');
		}
	}

	/**
	 * Test render().
	 *
	 * @access public
	 * @return void
	 */
	public function testRender() {
		$m = new Mustache();

		$this->assertEquals('', $m->render(''));
		$this->assertEquals('foo', $m->render('foo'));
		$this->assertEquals('', $m->render(null));

		$m2 = new Mustache('foo');
		$this->assertEquals('foo', $m2->render());

		$m3 = new Mustache('');
		$this->assertEquals('', $m3->render());

		$m3 = new Mustache();
		$this->assertEquals('', $m3->render(null));
	}

	/**
	 * Test render() with data.
	 *
	 * @access public
	 * @return void
	 */
	public function testRenderWithData() {
		$m = new Mustache('{{first_name}} {{last_name}}');
		$this->assertEquals('Charlie Chaplin', $m->render(null, array('first_name' => 'Charlie', 'last_name' => 'Chaplin')));
		$this->assertEquals('Zappa, Frank', $m->render('{{last_name}}, {{first_name}}', array('first_name' => 'Frank', 'last_name' => 'Zappa')));
	}

	/**
	 * Mustache should return the same thing when invoked multiple times.
	 *
	 * @access public
	 * @return void
	 */
	public function testMultipleInvocations() {
		$m = new Mustache('x');
		$first = $m->render();
		$second = $m->render();

		$this->assertEquals('x', $first);
		$this->assertEquals($first, $second);
	}

	/**
	 * Mustache should return the same thing when invoked multiple times.
	 *
	 * @access public
	 * @return void
	 */
	public function testMultipleInvocationsWithTags() {
		$m = new Mustache('{{one}} {{two}}', array('one' => 'foo', 'two' => 'bar'));
		$first = $m->render();
		$second = $m->render();

		$this->assertEquals('foo bar', $first);
		$this->assertEquals($first, $second);
	}

	/**
	 * Test everything in the `examples` directory.
	 *
	 * @dataProvider getExamples
	 * @access public
	 * @param mixed $class
	 * @param mixed $template
	 * @param mixed $output
	 * @return void
	 */
	public function testExamples($class, $template, $output) {
		$m = new $class;
		$this->assertEquals($output, $m->render($template));
	}

	/**
	 * Data provider for testExamples method.
	 *
	 * Assumes that an `examples` directory exists inside parent directory.
	 * This examples directory should contain any number of subdirectories, each of which contains
	 * three files: one Mustache class (.php), one Mustache template (.mustache), and one output file
	 * (.txt).
	 *
	 * This whole mess will be refined later to be more intuitive and less prescriptive, but it'll
	 * do for now. Especially since it means we can have unit tests :)
	 *
	 * @access public
	 * @return array
	 */
	public function getExamples() {
		$basedir = dirname(__FILE__) . '/../examples/';

		$ret = array();

		$files = new RecursiveDirectoryIterator($basedir);
		while ($files->valid()) {

			if ($files->hasChildren() && $children = $files->getChildren()) {
				$example  = $files->getSubPathname();
				$class    = null;
				$template = null;
				$output   = null;

				foreach ($children as $file) {
					if (!$file->isFile()) continue;

					$filename = $file->getPathInfo();
					$info = pathinfo($filename);

					switch($info['extension']) {
						case 'php':
							$class = $info['filename'];
							include_once($filename);
							break;

						case 'mustache':
							$template = file_get_contents($filename);
							break;

						case 'txt':
							$output = file_get_contents($filename);
							break;
					}
				}

				$ret[$example] = array($class, $template, $output);
			}

			$files->next();
		}
		return $ret;
	}
}