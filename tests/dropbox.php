<?php
/**
 * @author Hemant Mann <hemant.mann121@gmail.com>
 *
 * @copyright Copyright (c) 2017, ownCloud GmbH.
 * @license GPL-2.0
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 */

namespace Test\Files_external_dropbox;

use Prophecy\Argument;
use Test\Files\Storage\Storage;
use Kunnu\Dropbox\Models\Account as AccountModel;

class Dropbox extends Storage {
    private $config;

    protected $flysystem;

    protected function setUp() {
        $this->config = json_decode(file_get_contents('./config.json'), true);

        $this->flysystem = $this->prophesize('League\Flysystem\Filesystem');
        $this->instance = new \OCA\Files_external_dropbox\Storage\Dropbox($this->config);
        $this->instance->setFlysystem($this->flysystem->reveal());
        parent::setUp();
    }

    private function folderResponse($path = '/') {
		return [ 'path' => $path, 'type' => 'dir', 'timestamp' => 0 ];
    }

	public function testTestFunction() {
		$mockAdapter = $this->prophesize('OCA\Files_external_dropbox\Storage\Adapter');
		$this->instance->setAdapter($mockAdapter->reveal());

		$mockClient = $this->prophesize('Kunnu\Dropbox\Dropbox');
		$mockAdapter->getClient()->willReturn($mockClient->reveal());
		$mockClient->getCurrentAccount()->willReturn(new AccountModel(['account_id' => 1]));
		parent::testTestFunction();
	}

	/**
	 * @dataProvider directoryProvider
	 */
	public function testDirectories($directory) {
		$this->flysystem->has(Argument::type('string'))->willReturn(false);
		$this->assertFalse($this->instance->file_exists('/' . $directory));

		$this->flysystem->createDir(Argument::type('string'))->willReturn(true);
		$this->assertTrue($this->instance->mkdir('/' . $directory));

		$this->flysystem->has(Argument::type('string'))->willReturn(true);
		$this->flysystem->getMetadata(Argument::type('string'))->willReturn($this->folderResponse());
		$this->assertTrue($this->instance->file_exists('/' . $directory));
		$this->assertTrue($this->instance->is_dir('/' . $directory));
		$this->assertFalse($this->instance->is_file('/' . $directory));
		$this->assertEquals('dir', $this->instance->filetype('/' . $directory));
		$this->assertEquals(0, $this->instance->filesize('/' . $directory));
		$this->assertTrue($this->instance->isReadable('/' . $directory));
		$this->assertTrue($this->instance->isUpdatable('/' . $directory));
	}

	private function fileResponse($path, $mimeType = 'text/plan', $size = 10) {
		return [ 'path' => $path, 'type' => 'file', 'timestamp' => time(), 'size' => $size, 'mimetype' => $mimeType ];
    }

	/**
	 * test the various uses of file_get_contents and file_put_contents
	 *
	 * @dataProvider loremFileProvider
	 */
	public function testGetPutContents($sourceFile) {
		$sourceText = file_get_contents($sourceFile);

		//fill a file with string data
		$this->flysystem->put(Argument::type('string'), $sourceText)->willReturn(true);
		$this->instance->file_put_contents('/lorem.txt', $sourceText);

		$fileResponse = $this->fileResponse('lorem.txt');
		$this->flysystem->getMetadata(Argument::type('string'))->willReturn($fileResponse);
		$this->assertFalse($this->instance->is_dir('/lorem.txt'));

		$this->flysystem->read(Argument::type('string'))->willReturn($sourceText);
		$this->assertEquals($sourceText, $this->instance->file_get_contents('/lorem.txt'), 'data returned from file_get_contents is not equal to the source data');

		//empty the file
		$this->flysystem->put(Argument::type('string'), '')->willReturn(true);
		$this->instance->file_put_contents('/lorem.txt', '');
		$this->flysystem->read(Argument::type('string'))->willReturn('');
		$this->assertEquals('', $this->instance->file_get_contents('/lorem.txt'), 'file not emptied');
	}

	public function testMimeType() {
		$this->assertEquals('httpd/unix-directory', $this->instance->getMimeType('/'));
		$this->assertEquals(false, $this->instance->getMimeType('/non/existing/file'));
		$this->flysystem->put(Argument::any(), Argument::any())->willReturn(true);
		$this->flysystem->has(Argument::any())->willReturn(true);

		$textFile = \OC::$SERVERROOT . '/tests/data/lorem.txt';
		$this->instance->file_put_contents('/lorem.txt', file_get_contents($textFile, 'r'));
		$this->flysystem->getMetadata(Argument::type('string'))->willReturn($this->fileResponse('/lorem.txt'));
		$this->assertEquals('text/plain', $this->instance->getMimeType('/lorem.txt'));

		$pngFile = \OC::$SERVERROOT . '/tests/data/desktopapp.png';
		$this->instance->file_put_contents('/desktopapp.png', file_get_contents($pngFile, 'r'));
		$this->flysystem->has(Argument::type('string'))->willReturn($this->fileResponse('/desktopapp.png', 'image/png'));
		$this->assertEquals('image/png', $this->instance->getMimeType('/desktopapp.png'));

		$svgFile = \OC::$SERVERROOT . '/tests/data/desktopapp.svg';
		$this->instance->file_put_contents('/desktopapp.svg', file_get_contents($svgFile, 'r'));
		$this->flysystem->has(Argument::type('string'))->willReturn($this->fileResponse('/desktopapp.svg', 'image/svg+xml'));
		$this->assertEquals('image/svg+xml', $this->instance->getMimeType('/desktopapp.svg'));
	}

	public function initSourceAndTarget($source, $target = null) {
		$textFile = \OC::$SERVERROOT . '/tests/data/lorem.txt';
		$this->flysystem->put(Argument::type('string'), file_get_contents($textFile))->willReturn(true);
		$this->instance->file_put_contents($source, file_get_contents($textFile));
		if ($target) {
			$testContents = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
			$this->flysystem->put(Argument::type('string'), file_get_contents($testContents))->willReturn(true);
			$this->instance->file_put_contents($target, $testContents);
		}
	}

	/**
	 * @dataProvider copyAndMoveProvider
	 */
	public function testCopy($source, $target) {
		$this->initSourceAndTarget($source);

		$this->flysystem->has(Argument::type('string'))->willReturn(false);
		$this->flysystem->copy(Argument::type('string'), Argument::type('string'))->willReturn(true);
		$this->instance->copy($source, $target);

		$this->flysystem->has(Argument::type('string'))->willReturn(true);
		$this->assertTrue($this->instance->file_exists($target), $target . ' was not created');
		
		$this->flysystem->read(Argument::type('string'))->willReturn(file_get_contents(\OC::$SERVERROOT . '/tests/data/lorem.txt'));
		$this->assertSameAsLorem($target);
		$this->assertTrue($this->instance->file_exists($source), $source . ' was deleted');
	}
}
