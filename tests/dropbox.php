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
}
