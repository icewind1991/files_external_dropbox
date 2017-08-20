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

    protected function setUp() {
        $this->config = json_decode(file_get_contents('./config.json'), true);
        $this->instance = new \OCA\Files_external_dropbox\Storage\Dropbox($this->config);
        parent::setUp();
    }

    public function directoryProvider() {
		return [
			['folder'],
			[' folder'],
			['folder with space'],
			['spéciäl földer'],
			['test single\'quote'],
		];
	}

    /**
     * @dataProvider directoryProvider
     */
    public function testDirectories($directory) {
        $this->assertFalse($this->instance->file_exists('/' . $directory));

        $this->assertTrue($this->instance->mkdir('/' . $directory));

        $this->assertTrue($this->instance->file_exists('/' . $directory));
        $this->assertTrue($this->instance->is_dir('/' . $directory));
        $this->assertFalse($this->instance->is_file('/' . $directory));
        $this->assertEquals('dir', $this->instance->filetype('/' . $directory));
        $this->assertEquals(0, $this->instance->filesize('/' . $directory));
        $this->assertTrue($this->instance->isReadable('/' . $directory));
        $this->assertTrue($this->instance->isUpdatable('/' . $directory));

        $this->assertFalse($this->instance->mkdir('/' . $directory)); //can't create existing folders
        $this->assertTrue($this->instance->rmdir('/' . $directory));

        $this->wait();
        $this->assertFalse($this->instance->file_exists('/' . $directory));

        $this->assertFalse($this->instance->rmdir('/' . $directory)); //can't remove non existing folders
    }

    public function testCheckUpdate() {
        $this->assertTrue(true);
    }

    public function testStat() {
        $textFile = \OC::$SERVERROOT . '/tests/data/lorem.txt';
        $ctimeStart = time();
        $this->instance->file_put_contents('/lorem.txt', file_get_contents($textFile));
        $this->assertTrue($this->instance->isReadable('/lorem.txt'));
        $ctimeEnd = time();
        $mTime = $this->instance->filemtime('/lorem.txt');
        $this->assertTrue($this->instance->hasUpdated('/lorem.txt', $ctimeStart - 5));
        $this->assertTrue($this->instance->hasUpdated('/', $ctimeStart - 5));

        // check that ($ctimeStart - 5) <= $mTime <= ($ctimeEnd + 1)
        $this->assertGreaterThanOrEqual(($ctimeStart - 5), $mTime);
        $this->assertLessThanOrEqual(($ctimeEnd + 1), $mTime);
        $this->assertEquals(filesize($textFile), $this->instance->filesize('/lorem.txt'));

        $stat = $this->instance->stat('/lorem.txt');
        //only size and mtime are required in the result
        $this->assertEquals($stat['size'], $this->instance->filesize('/lorem.txt'));
        $this->assertEquals($stat['mtime'], $mTime);
    }
}
