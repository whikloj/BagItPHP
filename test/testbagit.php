<?php

require_once 'lib/bagit.php';

/**
 * Recursively delete a directory.
 */
function rrmdir($dir) {
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (filetype($dir . "/" . $object) == "dir")
                    rrmdir($dir . "/" . $object);
                else
                    unlink($dir . "/" . $object);
            }
        }
        reset($objects);
        rmdir($dir);
    }
}

/**
 * Get a temporary name and create a directory there.
 */
function tmpdir($prefix='bag') {
    $dir = tempnam(sys_get_temp_dir(), $prefix);
    unlink($dir);
    mkdir($dir, 0700);
    return $dir;
}

class BagPhpTest extends PHPUnit_Framework_TestCase {
    var $tmpdir;
    var $bag;

    public function setUp() {
        $this->tmpdir = tmpdir();
        $this->bag = new BagIt($this->tmpdir);
    }

    public function tearDown() {
        rrmdir($this->tmpdir);
    }

    public function testBagDirectory() {
        $this->assertEquals($this->tmpdir, $this->bag->bagDirectory);
    }

    public function testExtended() {
        $this->assertTrue($this->bag->extended);

        $tmp2 = tmpdir();
        try {
            touch($tmp2 . "/bag-info.txt");
            $bag = new BagIt($tmp2, false, false);
            $this->assertFalse($bag->extended);
        } catch (Exception $e) {
            rrmdir($tmp2);
            throw $e;
        }
        rrmdir($tmp2);
    }

    public function testHashEncoding() {
        $this->assertEquals('sha1', $this->bag->hashEncoding);

        $tmp2 = tmpdir();
        try {
            touch($tmp2 . "/manifest-md5.txt");
            $bag = new BagIt($tmp2);
            $this->assertEquals('md5', $bag->hashEncoding);
        } catch (Exception $e) {
            rrmdir($tmp2);
            throw $e;
        }
        rrmdir($tmp2);
    }

    public function testBagMajorVersion() {
        $this->assertEquals(0, $this->bag->bagMajorVersion);

        $tmp2 = tmpdir();
        try {
            file_put_contents(
                $tmp2 . "/bagit.txt",
                "BagIt-Version: 1.3\n" .
                "Tag-File-Character-Encoding: ISO-8859-1\n"
            );
            $bag = new BagIt($tmp2);
            $this->assertEquals(1, $bag->bagMajorVersion);
        } catch (Exception $e) {
            rrmdir($tmp2);
            throw $e;
        }
        rrmdir($tmp2);
    }

    public function testBagMinorVersion() {
        $this->assertEquals(96, $this->bag->bagMinorVersion);

        $tmp2 = tmpdir();
        try {
            file_put_contents(
                $tmp2 . "/bagit.txt",
                "BagIt-Version: 1.3\n" .
                "Tag-File-Character-Encoding: ISO-8859-1\n"
            );
            $bag = new BagIt($tmp2);
            $this->assertEquals(3, $bag->bagMinorVersion);
        } catch (Exception $e) {
            rrmdir($tmp2);
            throw $e;
        }
        rrmdir($tmp2);
    }

    public function testTagFileEncoding() {
        $this->assertEquals('UTF-8', $this->bag->tagFileEncoding);

        $tmp2 = tmpdir();
        try {
            file_put_contents(
                $tmp2 . "/bagit.txt",
                "BagIt-Version: 1.3\n" .
                "Tag-File-Character-Encoding: ISO-8859-1\n"
            );
            $bag = new BagIt($tmp2);
            $this->assertEquals('ISO-8859-1', $bag->tagFileEncoding);
        } catch (Exception $e) {
            rrmdir($tmp2);
            throw $e;
        }
        rrmdir($tmp2);
    }

    public function testDataDirectory() {
        $this->assertEquals(
            $this->tmpdir . "/data",
            $this->bag->dataDirectory
        );
        $this->assertTrue(is_dir($this->bag->dataDirectory));
    }

    public function testBagitFile() {
        $this->assertEquals(
            $this->tmpdir . "/bagit.txt",
            $this->bag->bagitFile
        );
        $this->assertFileExists($this->bag->bagitFile);
        $this->assertEquals(
            "BagId-Version: 0.96\n" .
            "Tag-File-Character-Encoding: UTF-8\n",
            file_get_contents($this->bag->bagitFile)
        );
    }

    public function testManifestFile() {
        $this->assertEquals(
            $this->tmpdir . "/manifest-sha1.txt",
            $this->bag->manifestFile
        );
        $this->assertFileExists($this->bag->manifestFile);

        $tmp2 = tmpdir();
        try {
            touch($tmp2 . "/manifest-md5.txt");
            $bag = new BagIt($tmp2);
            $this->assertEquals(
                $tmp2 . "/manifest-md5.txt",
                $bag->manifestFile
            );
        } catch (Exception $e) {
            rrmdir($tmp2);
            throw $e;
        }
        rrmdir($tmp2);
    }

    public function testTagManifestFile() {
        $this->assertEquals(
            $this->tmpdir . "/tagmanifest-sha1.txt",
            $this->bag->manifestFile
        );
        $this->assertFileExists($this->bag->manifestFile);

        $tmp2 = tmpdir();
        try {
            touch($tmp2 . "/tagmanifest-md5.txt");
            $bag = new BagIt($tmp2);
            $this->assertEquals(
                $tmp2 . "/tagmanifest-md5.txt",
                $bag->tagManifestFile
            );
        } catch (Exception $e) {
            rrmdir($tmp2);
            throw $e;
        }
        rrmdir($tmp2);
    }

    public function testFetchFile() {
        $this->assertEquals(
            $this->tmpdir . "/fetch.txt",
            $this->bag->fetchFile
        );
        $this->assertFileExists($this->bag->fetchFile);
    }

    public function testBagInfoFile() {
        $this->assertEquals(
            $this->tmpdir . "/bag-info.txt",
            $this->bag->bagInfoFile
        );
        $this->assertFileExists($this->bag->bagInfoFile);
    }

    public function testManifestContents() {
        $this->assertEquals(0, count($this->bag->manifestContents));

        $tmp2 = tmpdir();
        try {
            file_put_contents(
                $tmp2 . "/manifest-sha1.txt",
                "CHECK1 File-1\n" .
                "CHECK2 File-2\n"
            );
            $bag = new BagIt($tmp2);
            $this->assertNotNull($bag->manifestContents);
            $this->assertArrayHasKey("File-1", $bag->manifestContents);
            $this->assertArrayHasKey("File-2", $bag->manifestContents);
        } catch (Exception $e) {
            rrmdir($tmp2);
            throw $e;
        }
        rrmdir($tmp2);
    }

    public function testTagManifestContents() {
        $this->assertEquals(0, count($this->bag->tagManifestContents));

        $tmp2 = tmpdir();
        try {
            file_put_contents(
                $tmp2 . "/tagmanifest-sha1.txt",
                "CHECK1 File-1\n" .
                "CHECK2 File-2\n"
            );
            $bag = new BagIt($tmp2);
            $this->assertNotNull($bag->tagManifestContents);
            $this->assertArrayHasKey("File-1", $bag->tagManifestContents);
            $this->assertArrayHasKey("File-2", $bag->tagManifestContents);
        } catch (Exception $e) {
            rrmdir($tmp2);
            throw $e;
        }
        rrmdir($tmp2);
    }

    public function testFetchContents() {
        $this->assertEquals(0, count($this->bag->fetchContents));

        $tmp2 = tmpdir();
        try {
            file_put_contents(
                $tmp2 . "/fetch.txt",
                "http://www.google.com - google/index.html\n" .
                "http://www.yahoo.com - yahoo/index.html\n"
            );
            $bag = new BagIt($tmp2);
            $this->assertNotNull($bag->fetchContents);
            $this->assertArrayHasKey("google/index.html", $bag->fetchContents);
            $this->assertArrayHasKey("yahoo/index.html", $bag->fetchContents);
        } catch (Exception $e) {
            rrmdir($tmp2);
            throw $e;
        }
        rrmdir($tmp2);
    }

    public function testBagInfoContents() {
        $this->assertEquals(0, count($this->bag->bagInfoContents));

        $tmp2 = tmpdir();
        try {
            file_put_contents(
                $tmp2 . "/bag-info.txt",
                "Source-organization: University of Virginia Alderman Library\n" .
                "Contact-name: Eric Rochester\n" .
                "Bag-size: very, very small\n"
            );
            $bag = new BagIt($tmp2);
            $this->assertNotNull($bag->bagInfoContents);
            $this->assertArrayHasKey("source-organization", $bag->fetchContents);
            $this->assertArrayHasKey("contact-name", $bag->fetchContents);
            $this->assertArrayHasKey("bag-size", $bag->fetchContents);
            $this->assertArrayHasKey("Bag-size", $bag->fetchContents);
            $this->assertArrayHasKey("BAG-SIZE", $bag->fetchContents);
            $this->assertFalse(array_key_exists("bag-date", $bag->fetchContents));
        } catch (Exception $e) {
            rrmdir($tmp2);
            throw $e;
        }
        rrmdir($tmp2);
    }

    public function testBagCompression() {
        $this->assertEquals("tgz", $this->bag->bagCompression);
    }

    public function testBagErrors() {
        $this->assertInternalType('array', $this->bag->bagErrors);
        $this->assertEquals(0, count($this->bag->bagErrors));
    }

    public function testConstructorValidate() {
        $this->assertTrue($this->bag->isValid());
        $this->assertEquals(0, count($this->bag->bagErrors));

        $tmp = tmpdir();
        try {
            $bag = new BagIt($tmp, true);
            $this->assertFalse($bag->isValid());
            $this->assertGreaterThan(0, count($bag->bagErrors));
        } catch (Exception $e) {
            rrmdir($tmp);
            throw $e;
        }
        rrmdir($tmp);
    }

    public function testConstructorExtended() {
        $this->assertFileExists($this->tmpdir . '/bag-info.txt');
        $this->assertFileExists($this->tmpdir . '/fetch.txt');
        $this->assertFileExists($this->tmpdir . '/tagmanifest-sha1.txt');

        $tmp = tmpdir();
        try {
            $bag = new BagIt($tmp, false, false);
            $this->assertFalse(is_file($tmp . '/bag-info.txt'));
            $this->assertFalse(is_file($tmp . '/fetch.txt'));
            $this->assertFalse(is_file($tmp . '/tagmanifest-sha1.txt'));
        } catch (Exception $e) {
            rrmdir($tmp);
            throw $e;
        }
        rrmdir($tmp);
    }

    public function testConstructorFetch() {
        $tmp = tmpdir();
        try {
            file_put_contents(
                $tmp . "/fetch.txt",
                "http://www.google.com - google/index.html\n" .
                "http://www.yahoo.com - yahoo/index.html\n"
            );
            $bag = new BagIt($tmp, false, true, false);
            $this->assertFalse(
                is_file($bag->dataDirectory . '/google/index.html')
            );
            $this->assertFalse(
                is_file($bag->dataDirectory . '/yahoo/index.html')
            );
        } catch (Exception $e) {
            rrmdir($tmp);
            throw $e;
        }
        rrmdir($tmp);

        $tmp = tmpdir();
        try {
            file_put_contents(
                $tmp . "/fetch.txt",
                "http://www.google.com - google/index.html\n" .
                "http://www.yahoo.com - yahoo/index.html\n"
            );
            $bag = new BagIt($tmp, false, true, true);
            $this->assertFileExists($bag->dataDirectory . '/google/index.html');
            $this->assertFileExists($bag->dataDirectory . '/yahoo/index.html');
        } catch (Exception $e) {
            rrmdir($tmp);
            throw $e;
        }
        rrmdir($tmp);
    }

    public function testConstructorInvalidBagitFile() {
        $this->assertEquals(0, $this->bag->bagMajorVersion);

        $tmp = tmpdir();
        try {
            file_put_contents(
                $tmp . "/bagit.txt",
                "BagIt-Version: 1.3\n" .
                "Tag-File-Character-Encoding: ISO-8859-1\n"
            );
            $bag = new BagIt($tmp);

            $this->assertFalse($bag->isValid());

            $bagErrors = $bag->getBagErrors();
            $seen = false;
            foreach ($bagErrors as $err) {
                if ($err[0] == 'bagit') {
                    $seen = true;
                }
            }
            $this->assertTrue($seen);

        } catch (Exception $e) {
            rrmdir($tmp2);
            throw $e;
        }
        rrmdir($tmp2);
    }

    public function testIsValid() {
        $this->assertTrue($this->bag->isValid());
        $this->bag->validate();
        $this->assertFalse($this->bag->isValid());
    }

    public function testIsExtended() {
        $this->assertTrue($this->bag->isExtended());

        $tmp = tmpdir();
        try {
            file_put_contents(
                $tmp . "/bag-info.txt",
                "Source-organization: University of Virginia Alderman Library\n" .
                "Contact-name: Eric Rochester\n" .
                "Bag-size: very, very small\n"
            );
            $bag = new BagIt($tmp, false, false);
            $this->assertTrue($this->bag->isExtended());
        } catch (Exception $e) {
            rrmdir($tmp);
            throw $e;
        }
        rrmdir($tmp);

        $tmp = tmpdir();
        try {
            touch($tmp . '/fetch.txt');
            $bag = new BagIt($tmp, false, false);
            $this->assertTrue($this->bag->isExtended());
        } catch (Exception $e) {
            rrmdir($tmp);
            throw $e;
        }
        rrmdir($tmp);

        $tmp = tmpdir();
        try {
            touch($tmp . '/tagmanifest-sha1.txt');
            $bag = new BagIt($tmp, false, false);
            $this->assertTrue($this->bag->isExtended());
        } catch (Exception $e) {
            rrmdir($tmp);
            throw $e;
        }
        rrmdir($tmp);

        $tmp = tmpdir();
        try {
            $bag = new BagIt($tmp, false, false);
            $this->assertFalse($this->bag->isExtended());
        } catch (Exception $e) {
            rrmdir($tmp);
            throw $e;
        }
        rrmdir($tmp);
    }

    public function testGetBagInfo() {
        $bagInfo = $this->bag->getBagInfo();

        $this->assertInternalType('array', $bagInfo);

        $this->assertArrayHasKey('version', $bagInfo);
        $this->assertArrayHasKey('encoding', $bagInfo);
        $this->assertArrayHasKey('hash', $bagInfo);

        $this->assertEquals('0.96', $bagInfo['version']);
        $this->assertEquals('UTF-8', $bagInfo['encoding']);
        $this->assertEquals('sha1', $bagInfo['hash']);
    }

    public function testGetDataDirectory() {
        $dataDir = $this->bag->getDataDirectory();
        $this->assertStringStartsWith($this->tmpdir, $dataDir);
    }

    public function testGetHashEncoding() {
        $hash = $this->bag->getHashEncoding();
        $this->assertEquals('sha1', $hash);
    }

    public function testSetHashEncodingMD5() {
        $this->bag->setHashEncoding('md5');
        $this->assertEquals('md5', $this->bag->getHashEncoding());
    }

    public function testSetHashEncodingSHA1() {
        $this->bag->setHashEncoding('md5');
        $this->bag->setHashEncoding('sha1');
        $this->assertEquals('sha1', $this->bag->getHashEncoding());
    }

    /**
     * @expectedException Exception
     */
    public function testSetHashEncodingERR() {
        $this->bag->setHashEncoding('err');
    }

    public function testGetBagContents() {
        $bagContents = $this->bag->getBagContents();

        $this->assertInternalType('array', $bagContents);
        $this->assertEquals(0, count($bagContents));

        $tmp = tmpdir();
        try {
            file_put_contents(
                $tmp . "/data/something.txt",
                "Source-organization: University of Virginia Alderman Library\n" .
                "Contact-name: Eric Rochester\n" .
                "Bag-size: very, very small\n"
            );
            $bag = new BagIt($tmp);

            $bagContents = $bag->getBagContents();
            $this->assertEquals(1, count($bagContents));
            $this->assertEquals($tmp . '/data/something.txt', $bagContents[0]);
        } catch (Exception $e) {
            rrmdir($tmp);
            throw $e;
        }
        rrmdir($tmp);
    }

    public function testGetBagErrors() {
        $bagErrors = $this->bag->getBagErrors();
        $this->assertInternalType('array', $bagErrors);
        $this->assertEquals(0, count($bagErrors));

        $this->bag->validate();
        $this->assertGreaterThan(0, count($this->bag->getBagErrors()));
    }

    public function testGetBagErrorsValidate() {
        $bagErrors = $this->bag->getBagErrors(true);
        $this->assertInternalType('array', $bagErrors);
        $this->assertGreaterThan(0, count($bagErrors));
    }

    public function testValidateMissingBagFile() {
        $this->bag->validate();
        $bagErrors = $this->bag->getBagErrors();

        $this->assertFalse($this->bag->isValid());
        $this->assertContains('Missing "bagit.txt" file.', $bagErrors);
        $this->assertContains('Missing manifest file.', $bagErrors);
        // TODO: Add other required files here.
    }

    public function testValidateMissingDataFile() {
        $tmp = tmpdir();
        try {
            file_put_contents(
                $tmp . "/manifest-sha1.txt",
                "CHECKSUM data/missing.txt\n"
            );
            $bag = new BagIt($tmp);
            $bag->validate();
            $bagErrors = $bag->getBagErrors();

            $this->assertFalse($bag->isValid());
            $this->assertContains(
                'Missing data file: "data/missing.txt".',
                $bagErrors
            );
        } catch (Exception $e) {
            rrmdir($tmp);
            throw $e;
        }
        rrmdir($tmp);
    }

    public function testValidateChecksum() {
        $tmp = tmpdir();
        try {
            file_put_contents(
                $tmp . "/manifest-sha1.txt",
                "CHECKSUM data/missing.txt\n"
            );
            mkdir($tmp . '/data');
            touch($tmp . '/data/missing.txt');
            $bag = new BagIt($tmp);
            $bag->validate();
            $bagErrors = $bag->getBagErrors();

            $this->assertFalse($bag->isValid());
            $this->assertContains(
                'Wrong SHA1 checksum for data file "data/missing.txt".',
                $bagErrors
            );
        } catch (Exception $e) {
            rrmdir($tmp);
            throw $e;
        }
        rrmdir($tmp);
    }

    public function testUpdateCreateMissing() {
        $tmp = tmpdir();
        try {
            $bag = new BagIt($tmp);
            $bag->update();

            $this->assertFileExists($tmp . '/bagit.txt');
            $this->assertFileExists($tmp . '/manifest-sha1.txt');
            $this->assertTrue(is_dir($tmp . '/data'));

        } catch (Exception $e) {
            rrmdir($tmp);
            throw $e;
        }
        rrmdir($tmp);
    }

    public function testUpdateSanitize() {
        $tmp = tmpdir();
        try {
            mkdir($tmp . '/data');
            touch($tmp . '/data/has space');
            touch($tmp . '/data/PRN');
            touch($tmp . '/data/backup~');
            touch($tmp . '/data/.hidden');
            touch($tmp . '/data/quoted "yep" quoted');

            $bag = new BagIt($tmp);
            $bag->update();

            $this->assertFalse(is_file($tmp . '/data/has space'));
            $this->assertFileExists($tmp . '/data/has_space');

            $this->assertFalse(is_file($tmp . '/data/PRN'));
            $this->assertEquals(1, count(glob($tmp . '/data/prn_*')));

            $this->assertFalse(is_file($tmp . '/data/backup~'));

            $this->assertFalse(is_file($tmp . '/data/.hidden'));

            $this->assertFalse(is_file($tmp . '/data/quoted "yep" quoted'));
            $this->assertFileExists($tmp . '/data/quoted_yep_quoted');

        } catch (Exception $e) {
            rrmdir($tmp);
            throw $e;
        }
        rrmdir($tmp);
    }

    public function testUpdateChecksums() {
        $tmp = tmpdir();
        try {
            file_put_contents(
                $tmp . "/manifest-sha1.txt",
                "CHECKSUM data/missing.txt\n"
            );
            mkdir($tmp . '/data');
            file_put_contents(
                $tmp . '/data/missing.txt',
                'This space intentionally left blank.\n'
            );
            $bag = new BagIt($tmp);
            $bag->update();

            $this->assertEquals(
                '47659743df0b95772f7ca1b8b6f2ad2dcb196cfd data/missing.txt\n',
                file_get_contents($tmp . '/manifest-sha1.txt')
            );
            $this->assertEquals(
                '47659743df0b95772f7ca1b8b6f2ad2dcb196cfd',
                $bag->manifestContents['data/missing.txt']
            );

        } catch (Exception $e) {
            rrmdir($tmp);
            throw $e;
        }
        rrmdir($tmp);
    }

    public function testUpdateNewFiles() {
        $tmp = tmpdir();
        try {
            mkdir($tmp . '/data');
            file_put_contents(
                $tmp . '/data/missing.txt',
                'This space intentionally left blank.\n'
            );
            $bag = new BagIt($tmp);
            $bag->update();

            $this->assertEquals(
                '47659743df0b95772f7ca1b8b6f2ad2dcb196cfd data/missing.txt\n',
                file_get_contents($tmp . '/manifest-sha1.txt')
            );
            $this->assertEquals(
                '47659743df0b95772f7ca1b8b6f2ad2dcb196cfd',
                $bag->manifestContents['data/missing.txt']
            );

        } catch (Exception $e) {
            rrmdir($tmp);
            throw $e;
        }
        rrmdir($tmp);
    }

    public function testUpdateDeletedFiles() {
        $tmp = tmpdir();
        try {
            file_put_contents(
                $tmp . "/manifest-sha1.txt",
                "CHECKSUM data/missing.txt\n"
            );
            mkdir($tmp . '/data');
            $bag = new BagIt($tmp);
            $bag->update();

            $this->assertEquals(
                '',
                file_get_contents($tmp . '/manifest-sha1.txt')
            );
            $this->assertFalse(array_key_exists('data/missing.txt'));

        } catch (Exception $e) {
            rrmdir($tmp);
            throw $e;
        }
        rrmdir($tmp);
    }

    public function testUpdateExtended() {
        $tmp = tmpdir();
        try {
            $bag = new BagIt($tmp);
            $bag->update();

            $this->assertFileExists($tmp . '/bag-info.txt');
            $this->assertFileExists($tmp . '/tagmanifest-sha1.txt');
            $this->assertFileExists($tmp . '/fetch.txt');

        } catch (Exception $e) {
            rrmdir($tmp);
            throw $e;
        }
        rrmdir($tmp);
    }

    public function testFetch() {
        $tmp = tmpdir();
        try {
            mkdir($tmp . '/data');
            file_put_contents(
                $tmp . '/data/missing.txt',
                'This space intentionally left blank.\n'
            );
            file_put_contents(
                $tmp . "/fetch.txt",
                "http://www.google.com - data/google/index.html\n" .
                "http://www.yahoo.com - data/yahoo/index.html\n"
            );
            $bag = new BagIt($tmp);

            $this->assertFalse(is_file($tmp . 'data/google/index.html'));
            $this->assertFalse(is_file($tmp . 'data/yahoo/index.html'));

            $bag->fetch();

            $this->assertFileExists($tmp . 'data/google/index.html');
            $this->assertFileExists($tmp . 'data/yahoo/index.html');

        } catch (Exception $e) {
            rrmdir($tmp);
            throw $e;
        }
        rrmdir($tmp);
    }

    public function testAddFetchEntries() {
        $tmp = tmpdir();
        try {
            mkdir($tmp . '/data');
            file_put_contents(
                $tmp . '/data/missing.txt',
                'This space intentionally left blank.\n'
            );
            file_put_contents(
                $tmp . "/fetch.txt",
                "http://www.google.com - data/google/index.html\n" .
                "http://www.yahoo.com - data/yahoo/index.html\n"
            );
            $bag = new BagIt($tmp);

            $bag->addFetchEntries(
                array(array('http://www.scholarslab.org/',
                            'data/scholarslab/index.html'))
            );

            $this->assertEquals(
                "http://www.google.com - data/google/index.html\n" .
                "http://www.yahoo.com - data/yahoo/index.html\n" .
                "http://www.scholarslab.org/ - data/scholarslab/index.html",
                file_get_contents($tmp . '/fetch.txt')
            );

            $this->assertArrayHasKey(
                'http://www.google.com',
                $bag->fetchContents
            );
            $this->assertArrayHasKey(
                'http://www.yahoo.com',
                $bag->fetchContents
            );
            $this->assertArrayHasKey(
                'http://www.scholarslab.com',
                $bag->fetchContents
            );

        } catch (Exception $e) {
            rrmdir($tmp);
            throw $e;
        }
        rrmdir($tmp);
    }

    public function testAddFetchEntriesReplace() {
        $tmp = tmpdir();
        try {
            mkdir($tmp . '/data');
            file_put_contents(
                $tmp . '/data/missing.txt',
                'This space intentionally left blank.\n'
            );
            file_put_contents(
                $tmp . "/fetch.txt",
                "http://www.google.com - data/google/index.html\n" .
                "http://www.yahoo.com - data/yahoo/index.html\n"
            );
            $bag = new BagIt($tmp);

            $bag->addFetchEntries(
                array(array('http://www.scholarslab.org/',
                            'data/scholarslab/index.html')),
                false
            );

            $this->assertEquals(
                "http://www.scholarslab.org/ - data/scholarslab/index.html",
                file_get_contents($tmp . '/fetch.txt')
            );

            $this->assertFalse(
                array_key_exists('http://www.google.com', $bag->fetchContents)
            );
            $this->assertFalse(
                array_key_exists('http://www.yahoo.com', $bag->fetchContents)
            );
            $this->assertArrayHasKey(
                'http://www.scholarslab.com',
                $bag->fetchContents
            );

        } catch (Exception $e) {
            rrmdir($tmp);
            throw $e;
        }
        rrmdir($tmp);
    }

    public function testPackage() {
        $tmp = tmpdir();
        try {
            mkdir($tmp . '/data');
            file_put_contents(
                $tmp . '/data/missing.txt',
                'This space intentionally left blank.\n'
            );
            file_put_contents(
                $tmp . "/fetch.txt",
                "http://www.google.com - data/google/index.html\n" .
                "http://www.yahoo.com - data/yahoo/index.html\n"
            );
            $bag = new BagIt($tmp);

            $bag->update();

            $bag->package($tmp . '/../bagtmp1.tgz', 'tgz');
            $this->assertFileExists($tmp . '/../bagtmp1.tgz');

            $bag->package($tmp . '/../bagtmp2', 'tgz');
            $this->assertFileExists($tmp . '/../bagtmp2.tgz');

            $bag->package($tmp . '/../bagtmp1.zip', 'zip');
            $this->assertFileExists($tmp . '/../bagtmp1.zip');

            $bag->package($tmp . '/../bagtmp2', 'zip');
            $this->assertFileExists($tmp . '/../bagtmp2.zip');

            // TODO: Test the contents of the package.

        } catch (Exception $e) {
            rrmdir($tmp);
            throw $e;
        }
        rrmdir($tmp);
    }

}

?>