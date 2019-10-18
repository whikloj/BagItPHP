<?php

namespace ScholarsLab\BagIt\Test;

use PHPUnit\Framework\TestCase;
use ScholarsLab\BagIt\BagIt;
use ScholarsLab\BagIt\BagItManifest;
use ScholarsLab\BagIt\BagItUtils;

/**
 * Class BagItTest
 * @package ScholarsLab\BagIt\Test
 * @coversDefaultClass \ScholarsLab\BagIt\BagIt
 */
class BagItTest extends TestCase
{
    /**
     * @var string
     */
    private $tmpdir;

    /**
     * @var BagIt
     */
    private $bag;

    /**
     * List of valid hash algorithms.
     *
     * @var array
     */
    private $validHashAlgos;

    private function createBagItTxt($dirname)
    {
        file_put_contents(
            "$dirname/bagit.txt",
            "BagIt-Version: 1.3\n" .
            "Tag-File-Character-Encoding: ISO-8859-1\n"
        );
    }

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        // Generate a list of algorithms PHP on this system supports.
        $this->validHashAlgos = array_filter(
            hash_algos(),
            function ($item) {
                return in_array($item, array_values(BagItManifest::HASH_ALGORITHMS));
            }
        );
        array_walk($this->validHashAlgos, function (&$item) {
            $item = array_flip(BagItManifest::HASH_ALGORITHMS)[$item];
        });
    }

    public function setUp()
    {
        $this->tmpdir = BagItUtils::tmpdir();
        $this->bag = new BagIt($this->tmpdir);
    }

    public function tearDown()
    {
        BagItUtils::rrmdir($this->tmpdir);
    }

    /**
     * Test creation of bag directory.
     *
     * @group BagIt
     * @covers ::getBagDirectory
     */
    public function testBagDirectory()
    {
        $this->assertEquals($this->tmpdir, $this->bag->getBagDirectory());
    }

    /**
     * Test extended Bag info.
     * @group BagIt
     * @covers ::isExtended
     */
    public function testExtended()
    {
        $this->assertTrue($this->bag->isExtended());

        $tmp2 = BagItUtils::tmpdir();
        mkdir($tmp2);
        touch($tmp2 . "/bag-info.txt");
        $bag = new BagIt($tmp2, false, false);
        $this->assertFalse($bag->isExtended());

        BagItUtils::rrmdir($tmp2);
    }

    /**
     * Test parsing bag version.
     * @group BagIt
     * @covers ::getBagInfo
     */
    public function testBagVersion()
    {
        $bagInfo = $this->bag->getBagInfo();
        $this->assertEquals(0, $bagInfo['version_parts']['major']);
        $this->assertEquals(96, $bagInfo['version_parts']['minor']);

        $tmp2 = BagItUtils::tmpdir();
        mkdir($tmp2);
        $this->createBagItTxt($tmp2);
        $bag = new BagIt($tmp2);
        $newBagInfo = $bag->getBagInfo();
        $this->assertEquals(1, $newBagInfo['version_parts']['major']);
        $this->assertEquals(3, $newBagInfo['version_parts']['minor']);

        BagItUtils::rrmdir($tmp2);
    }

    /**
     * Test changing file encoding.
     * @group BagIt
     * @covers ::getFileEncoding
     */
    public function testTagFileEncoding()
    {
        $this->assertEquals('UTF-8', $this->bag->getFileEncoding());

        $tmp2 = BagItUtils::tmpdir();
        mkdir($tmp2);
        $this->createBagItTxt($tmp2);
        $bag = new BagIt($tmp2);
        $this->assertEquals('ISO-8859-1', $bag->getFileEncoding());

        BagItUtils::rrmdir($tmp2);
    }

    /**
     * Test the creation of the manifest file.
     * @group BagIt
     * @covers ::getManifests
     */
    public function testManifest()
    {
        $this->assertInstanceOf(
            '\ScholarsLab\BagIt\BagItManifest',
            $this->bag->getManifests()[BagIt::DEFAULT_HASH_ALGORITHM]
        );
    }

    /**
     * Test the create of the tagmanifest file.
     * @group BagIt
     * @covers ::getManifests
     */
    public function testTagManifest()
    {
        $this->assertInstanceOf(
            '\ScholarsLab\BagIt\BagItManifest',
            $this->bag->getManifests()[BagIt::DEFAULT_HASH_ALGORITHM]
        );
    }

    /**
     * Test the creation of the bagit fetch file.
     * @group BagIt
     * @covers ::getFetch
     */
    public function testFetch()
    {
        $this->assertInstanceOf('\ScholarsLab\BagIt\BagItFetch', $this->bag->getFetch());
    }

    /**
     * Test the creation of a default bag-info.txt file.
     * @group BagIt
     * @covers ::getBagDirectory
     */
    public function testBagInfoFile()
    {
        $this->assertEquals(
            $this->tmpdir . "/bag-info.txt",
            $this->bag->getBagDirectory() . "/bag-info.txt"
        );
        $this->assertFileExists($this->bag->getBagDirectory() . "/bag-info.txt");
    }

    /**
     * Test BagIt constructor.
     * @group BagIt
     * @covers ::isExtended
     * @covers ::getBagInfoKeys
     * @covers ::hasBagInfoData
     */
    public function testBagInfoConstructor()
    {
        $tmp2 = BagItUtils::tmpdir();
        $bag = new BagIt($tmp2, false, false, false, array(
            'source-organization' => 'University of Virginia',
            'contact-name'        => 'Someone'
        ));
        $this->assertTrue($bag->isExtended());
        $this->assertNotNull($bag->getBagInfoKeys());
        $this->assertTrue($bag->hasBagInfoData("source-organization"));
        $this->assertTrue($bag->hasBagInfoData("contact-name"));
        $this->assertFalse($bag->hasBagInfoData("bag-date"));

        BagItUtils::rrmdir($tmp2);
    }

    /**
     * Test bagit info function.
     * @group BagIt
     * @covers ::hasBagInfoData
     * @covers ::getBagInfoKeys
     */
    public function testBagInfoData()
    {

        $tmp2 = BagItUtils::tmpdir();
        mkdir($tmp2);
        $this->createBagItTxt($tmp2);
        file_put_contents(
            $tmp2 . "/bag-info.txt",
            "Source-organization: University of Virginia Alderman Library\n" .
            "Contact-name: Eric Rochester\n" .
            "Bag-size: very, very small\n"
        );
        $bag = new BagIt($tmp2);
        $this->assertNotNull($bag->getBagInfoKeys());
        $this->assertCount(3, $bag->getBagInfoKeys());
        $this->assertTrue($bag->hasBagInfoData("Source-organization"));
        $this->assertTrue($bag->hasBagInfoData("Contact-name"));
        $this->assertTrue($bag->hasBagInfoData("Bag-size"));
        $this->assertFalse($bag->hasBagInfoData("bag-size"));
        $this->assertFalse($bag->hasBagInfoData("BAG-SIZE"));
        $this->assertFalse($bag->hasBagInfoData("bag-date"));

        BagItUtils::rrmdir($tmp2);
    }

    /**
     * Test combining same bag-info key values to a single array.
     * @group BagIt
     * @covers ::getBagInfoData
     * @covers ::hasBagInfoData
     * @covers ::getBagInfoKeys
     */
    public function testBagInfoDuplicateData()
    {

        $tmp2 = BagItUtils::tmpdir();
        mkdir($tmp2);
        $this->createBagItTxt($tmp2);
        file_put_contents(
            $tmp2 . "/bag-info.txt",
            "Source-organization: University of Virginia Alderman Library\n" .
            "Contact-name: Eric Rochester\n" .
            "Bag-size: very, very small\n" .
            "DC-Author: Me\n" .
            "DC-Author: Myself\n" .
            "DC-Author: The other\n" .
            " and more\n"
        );
        $bag = new BagIt($tmp2);
        $this->assertNotNull($bag->getBagInfoKeys());
        $this->assertCount(4, $bag->getBagInfoKeys());

        $this->assertTrue($bag->hasBagInfoData('DC-Author'));
        $this->assertEquals(
            array( 'Me', 'Myself', 'The other and more' ),
            $bag->getBagInfoData('DC-Author')
        );

        BagItUtils::rrmdir($tmp2);
    }

    /**
     * Test combining same bag-info key values to a single array from setter.
     * @group BagIt
     * @covers ::setBagInfoData
     * @covers ::getBagInfoData
     */
    public function testBagInfoDuplicateSetBagData()
    {

        $tmp2 = BagItUtils::tmpdir();
        mkdir($tmp2);
        file_put_contents(
            $tmp2 . "/bag-info.txt",
            "Source-organization: University of Virginia Alderman Library\n" .
            "Contact-name: Eric Rochester\n" .
            "Bag-size: very, very small\n" .
            "DC-Author: Me\n" .
            "DC-Author: Myself\n" .
            "DC-Author: The other\n"
        );
        $bag = new BagIt($tmp2);

        $bag->setBagInfoData('First', 'This is the first tag value.');
        $bag->setBagInfoData('Second', 'This is the second tag value.');
        $bag->setBagInfoData('Second', 'This is the third tag value.');
        $bag->setBagInfoData('Third', 'This is the fourth tag value.');
        $bag->setBagInfoData('Third', 'This is the fifth tag value.');
        $bag->setBagInfoData('Third', 'This is the sixth tag value.');

        $this->assertEquals(
            'This is the first tag value.',
            $bag->getBagInfoData('First')
        );
        $this->assertEquals(
            array( 'This is the second tag value.', 'This is the third tag value.' ),
            $bag->getBagInfoData('Second')
        );
        $this->assertEquals(
            array(
                'This is the fourth tag value.',
                'This is the fifth tag value.',
                'This is the sixth tag value.'
            ),
            $bag->getBagInfoData('Third')
        );

        BagItUtils::rrmdir($tmp2);
    }

    /**
     * Test clearing a bag-info key/value pair.
     * @group BagIt
     * @covers ::setBagInfoData
     * @covers ::clearBagInfoData
     * @covers ::getBagInfoData
     */
    public function testBagInfoDuplicateClearBagData()
    {

        $tmp2 = BagItUtils::tmpdir();
        mkdir($tmp2);
        file_put_contents(
            $tmp2 . "/bag-info.txt",
            "Source-organization: University of Virginia Alderman Library\n" .
            "Contact-name: Eric Rochester\n" .
            "Bag-size: very, very small\n" .
            "DC-Author: Me\n" .
            "DC-Author: Myself\n" .
            "DC-Author: The other\n"
        );
        $bag = new BagIt($tmp2);

        $bag->setBagInfoData('First', 'This is the first tag value.');
        $bag->setBagInfoData('Second', 'This is the second tag value.');
        $bag->setBagInfoData('Second', 'This is the third tag value.');
        $bag->setBagInfoData('Third', 'This is the fourth tag value.');
        $bag->setBagInfoData('Third', 'This is the fifth tag value.');
        $bag->setBagInfoData('Third', 'This is the sixth tag value.');

        $this->assertEquals(
            'This is the first tag value.',
            $bag->getBagInfoData('First')
        );
        $this->assertEquals(
            array( 'This is the second tag value.', 'This is the third tag value.' ),
            $bag->getBagInfoData('Second')
        );
        $this->assertEquals(
            array(
                'This is the fourth tag value.',
                'This is the fifth tag value.',
                'This is the sixth tag value.'
            ),
            $bag->getBagInfoData('Third')
        );

        $bag->clearBagInfoData('Third');
        $this->assertNotNull($bag->getBagInfoData('First'));
        $this->assertNotNull($bag->getBagInfoData('Second'));
        $this->assertNull($bag->getBagInfoData('Third'));

        BagItUtils::rrmdir($tmp2);
    }

    /**
     * Test clearing bag-info.txt values.
     * @group BagIt
     * @covers ::getBagInfoData
     * @covers ::clearAllBagInfo
     */
    public function testBagItClearAllBagInfo()
    {

        $tmp2 = BagItUtils::tmpdir();
        mkdir($tmp2);
        file_put_contents(
            $tmp2 . "/bag-info.txt",
            "Source-organization: University of Virginia Alderman Library\n" .
            "Contact-name: Eric Rochester\n" .
            "Bag-size: very, very small\n" .
            "DC-Author: Me\n" .
            "DC-Author: Myself\n" .
            "DC-Author: The other\n"
        );
        $bag = new BagIt($tmp2);

        $bag->clearAllBagInfo();

        $this->assertNull($bag->getBagInfoData('Source-organization'));
        $this->assertNull($bag->getBagInfoData('DC-Author'));

        BagItUtils::rrmdir($tmp2);
    }

    /**
     * Test bag-info.txt keys.
     * @group BagIt
     * @covers ::getBagInfoKeys
     * @covers ::hasBagInfoData
     */
    public function testBagItGetBagInfoKeys()
    {

        $tmp2 = BagItUtils::tmpdir();
        mkdir($tmp2);
        $this->createBagItTxt($tmp2);
        file_put_contents(
            $tmp2 . "/bag-info.txt",
            "Source-organization: University of Virginia Alderman Library\n" .
            "Contact-name: Eric Rochester\n" .
            "Bag-size: very, very small\n" .
            "DC-Author: Me\n" .
            "DC-Author: Myself\n" .
            "DC-Author: The other\n" .
            " and more\n"
        );
        $bag = new BagIt($tmp2);
        $keys = $bag->getBagInfoKeys();
        sort($keys);
        $expected = array('Bag-size', 'Contact-name', 'DC-Author', 'Source-organization');
        $this->assertEquals($expected, $keys);

        $this->assertTrue($bag->hasBagInfoData('DC-Author'));
        $this->assertEquals(
            array( 'Me', 'Myself', 'The other and more' ),
            $bag->getBagInfoData('DC-Author')
        );

        BagItUtils::rrmdir($tmp2);
    }

    /**
     * Test bag-info updates to file.
     * @group BagIt
     * @covers ::setBagInfoData
     * @covers ::update
     */
    public function testBagInfoDuplicateDataWrite()
    {

        $tmp2 = BagItUtils::tmpdir();
        mkdir($tmp2);
        mkdir("$tmp2/data");
        $this->createBagItTxt($tmp2);
        file_put_contents(
            $tmp2 . "/bag-info.txt",
            "Source-organization: University of Virginia Alderman Library\n" .
            "Contact-name: Eric Rochester\n" .
            "Bag-size: very, very small\n" .
            "DC-Author: Me\n" .
            "DC-Author: Myself\n" .
            "DC-Author: The other\n"
        );
        $bag = new BagIt($tmp2);

        $bag->setBagInfoData('First', 'This is the first tag value.');
        $bag->setBagInfoData('Second', 'This is the second tag value.');
        $bag->setBagInfoData('Second', 'This is the third tag value.');
        $bag->setBagInfoData('Third', 'This is the fourth tag value.');
        $bag->setBagInfoData('Third', 'This is the fifth tag value.');
        $bag->setBagInfoData('Third', 'This is the sixth tag value.');

        $bag->update();

        $this->assertEquals(
            "Source-organization: University of Virginia Alderman Library\n" .
            "Contact-name: Eric Rochester\n" .
            "Bag-size: very, very small\n" .
            "DC-Author: Me\n" .
            "DC-Author: Myself\n" .
            "DC-Author: The other\n" .
            "First: This is the first tag value.\n" .
            "Second: This is the second tag value.\n" .
            "Second: This is the third tag value.\n" .
            "Third: This is the fourth tag value.\n" .
            "Third: This is the fifth tag value.\n" .
            "Third: This is the sixth tag value.\n",
            file_get_contents("$tmp2/bag-info.txt")
        );

        BagItUtils::rrmdir($tmp2);
    }

    /**
     * Test writing of bag-info data into package and reading it back.
     *
     * @group BagIt
     * @covers ::hasBagInfoData
     * @covers ::setBagInfoData
     * @covers ::update
     * @covers ::package
     */
    public function testBagInfoWrite()
    {

        $tmp2 = BagItUtils::tmpdir();
        mkdir($tmp2);
        mkdir("$tmp2/data");

        file_put_contents(
            "$tmp2/bag-info.txt",
            "Source-organization: University of Virginia Alderman Library\n" .
            "Contact-name: Eric Rochester\n" .
            "Bag-size: very, very small\n"
        );
        $bag = new BagIt($tmp2);
        $this->assertNotNull($bag->getBagInfoKeys());

        $bag->setBagInfoData('First', 'This is the first tag value.');
        $bag->setBagInfoData('Second', 'This is the second tag value.');

        $bag->update();
        $bag->package("$tmp2.tgz");
        BagItUtils::rrmdir($tmp2);

        $bag2 = new BagIt("$tmp2.tgz");
        $tmp2 = $bag2->getBagDirectory();

        $this->assertTrue($bag2->hasBagInfoData('First'));
        $this->assertEquals(
            'This is the first tag value.',
            $bag2->getBagInfoData('First')
        );
        $this->assertTrue($bag2->hasBagInfoData('Second'));
        $this->assertEquals(
            'This is the second tag value.',
            $bag2->getBagInfoData('Second')
        );

        BagItUtils::rrmdir($tmp2);
    }

    /**
     * Another writing bag-info tags test.
     * @group BagIt
     * @covers ::setBagInfoData
     */
    public function testBagInfoWriteTagCase()
    {

        $tmp2 = BagItUtils::tmpdir();
        mkdir($tmp2);
        mkdir("$tmp2/data");

        $this->createBagItTxt($tmp2);
        file_put_contents(
            "$tmp2/bag-info.txt",
            "Source-organization: University of Virginia Alderman Library\n" .
            "Contact-name: Eric Rochester\n" .
            "Bag-size: very, very small\n"
        );
        $bag = new BagIt($tmp2);
        $this->assertNotNull($bag->getBagInfoKeys());

        $bag->setBagInfoData('First', 'This is the first tag value.');
        $bag->setBagInfoData('Second', 'This is the second tag value.');

        $bag->update();

        $this->assertEquals(
            "Source-organization: University of Virginia Alderman Library\n" .
            "Contact-name: Eric Rochester\n" .
            "Bag-size: very, very small\n" .
            "First: This is the first tag value.\n" .
            "Second: This is the second tag value.\n",
            file_get_contents("$tmp2/bag-info.txt")
        );

        if (file_exists($tmp2)) {
            BagItUtils::rrmdir($tmp2);
        }
        if (file_exists("$tmp2.tgz")) {
            unlink("$tmp2.tgz");
        }
    }

    /**
     * Test initial non-existance of bag-info data.
     * @group BagIt
     * @covers ::hasBagInfoData
     * @covers ::getBagInfoKeys
     */
    public function testBagInfoNull()
    {
        $this->assertFalse($this->bag->hasBagInfoData('hi'));
        $this->assertCount(0, $this->bag->getBagInfoKeys());
    }

    /**
     * Test case sensitive matching on bag-info keys.
     * @group BagIt
     * @covers ::hasBagInfoData
     */
    public function testHasBagInfoData()
    {
        $tmp2 = BagItUtils::tmpdir();
        mkdir($tmp2);
        mkdir("$tmp2/data");

        $this->createBagItTxt($tmp2);
        file_put_contents(
            "$tmp2/bag-info.txt",
            "Source-organization: University of Virginia Alderman Library\n" .
            "Contact-name: Eric Rochester\n" .
            "Bag-size: very, very small\n"
        );
        $bag = new BagIt($tmp2);

        $this->assertTrue($bag->hasBagInfoData('Source-organization'));
        $this->assertFalse($bag->hasBagInfoData('source-organization'));
        $this->assertFalse($bag->hasBagInfoData('SOURCE-ORGANIZATION'));
        $this->assertFalse($bag->hasBagInfoData('Source-Organization'));
        $this->assertFalse($bag->hasBagInfoData('SoUrCe-oRgAnIzAtIoN'));

        $this->assertTrue($bag->hasBagInfoData('Contact-name'));
        $this->assertFalse($bag->hasBagInfoData('contact-name'));
        $this->assertFalse($bag->hasBagInfoData('CONTACT-NAME'));
        $this->assertFalse($bag->hasBagInfoData('Contact-Name'));
        $this->assertFalse($bag->hasBagInfoData('CoNtAcT-NaMe'));

        $this->assertTrue($bag->hasBagInfoData('Bag-size'));
        $this->assertFalse($bag->hasBagInfoData('bag-size'));
        $this->assertFalse($bag->hasBagInfoData('BAG-SIZE'));
        $this->assertFalse($bag->hasBagInfoData('Bag-Size'));
        $this->assertFalse($bag->hasBagInfoData('BaG-SiZe'));

        $this->assertFalse($bag->hasBagInfoData('copyright-date'));
        $this->assertFalse($bag->hasBagInfoData('other-metadata'));
        $this->assertFalse($bag->hasBagInfoData('thrown-away-the-key'));

        BagItUtils::rrmdir($tmp2);
    }

    /**
     * Test case sensitive storage of bag-info values.
     * @group BagIt
     * @covers ::getBagInfoData
     */
    public function testGetBagInfoData()
    {
        $tmp2 = BagItUtils::tmpdir();
        mkdir($tmp2);
        mkdir("$tmp2/data");

        $this->createBagItTxt($tmp2);
        file_put_contents(
            "$tmp2/bag-info.txt",
            "Source-organization: University of Virginia Alderman Library\n" .
            "Contact-name: Eric Rochester\n" .
            "Bag-size: very, very small\n"
        );
        $bag = new BagIt($tmp2);

        $this->assertEquals('University of Virginia Alderman Library', $bag->getBagInfoData('Source-organization'));
        $this->assertNotEquals('University of Virginia Alderman Library', $bag->getBagInfoData('source-organization'));
        $this->assertNotEquals('University of Virginia Alderman Library', $bag->getBagInfoData('SOURCE-ORGANIZATION'));
        $this->assertNotEquals('University of Virginia Alderman Library', $bag->getBagInfoData('Source-Organization'));
        $this->assertNotEquals('University of Virginia Alderman Library', $bag->getBagInfoData('SoUrCe-oRgAnIzAtIoN'));

        $this->assertEquals('Eric Rochester', $bag->getBagInfoData('Contact-name'));
        $this->assertNotEquals('Eric Rochester', $bag->getBagInfoData('contact-name'));
        $this->assertNotEquals('Eric Rochester', $bag->getBagInfoData('CONTACT-NAME'));
        $this->assertNotEquals('Eric Rochester', $bag->getBagInfoData('Contact-Name'));
        $this->assertNotEquals('Eric Rochester', $bag->getBagInfoData('CoNtAcT-NaMe'));

        $this->assertEquals('very, very small', $bag->getBagInfoData('Bag-size'));
        $this->assertNotEquals('very, very small', $bag->getBagInfoData('bag-size'));
        $this->assertNotEquals('very, very small', $bag->getBagInfoData('BAG-SIZE'));
        $this->assertNotEquals('very, very small', $bag->getBagInfoData('Bag-Size'));
        $this->assertNotEquals('very, very small', $bag->getBagInfoData('BaG-SiZe'));

        $this->assertNull($bag->getBagInfoData('copyright-date'));
        $this->assertNull($bag->getBagInfoData('other-metadata'));
        $this->assertNull($bag->getBagInfoData('thrown-away-the-key'));

        BagItUtils::rrmdir($tmp2);
    }

    /**
     * Test case sensitivity with hasBagInfoData().
     * @group BagIt
     * @covers ::getBagInfoKeys
     * @covers ::hasBagInfoData
     */
    public function testSetBagInfoData()
    {
        $this->assertCount(0, $this->bag->getBagInfoKeys());
        $this->bag->setBagInfoData('hi', 'some value');

        $this->assertTrue($this->bag->hasBagInfoData('hi'));
        $this->assertFalse($this->bag->hasBagInfoData('HI'));
        $this->assertFalse($this->bag->hasBagInfoData('Hi'));
        $this->assertFalse($this->bag->hasBagInfoData('hI'));

        $this->assertEquals('some value', $this->bag->getBagInfoData('hi'));
        $this->assertCount(1, $this->bag->getBagInfoKeys());
    }

    /**
     * Test initial non-compression setting.
     * @group BagIt
     * @covers ::isCompressed
     * @covers ::checkCompressed
     */
    public function testBagCompression()
    {
        $this->assertFalse($this->bag->isCompressed());
    }

    /**
     * Test initial empty errors.
     * @group BagIt
     * @covers ::getBagErrors
     */
    public function testBagErrors()
    {
        $this->assertInternalType('array', $this->bag->getBagErrors());
        $this->assertCount(0, $this->bag->getBagErrors());
    }

    /**
     * Test constructor validates an invalid version correctly.
     * @group BagIt
     * @covers ::createBag
     * @covers ::validate
     */
    public function testConstructorValidate()
    {
        $this->assertTrue($this->bag->isValid());
        $this->assertCount(0, $this->bag->getBagErrors());

        $tmp = BagItUtils::tmpdir();
        mkdir($tmp);
        $this->createBagItTxt($tmp);
        $bag = new BagIt($tmp, true);
        $this->assertFalse($bag->isValid());
        $this->assertGreaterThan(0, count($bag->getBagErrors()));

        BagItUtils::rrmdir($tmp);
    }

    /**
     * Test disabling extended bag creation.
     * @group BagIt
     * @covers ::createBag
     * @covers ::createExtendedBag
     */
    public function testConstructorExtended()
    {
        $this->assertFileExists($this->tmpdir . '/bag-info.txt');
        $this->assertFileNotExists($this->tmpdir . '/fetch.txt');
        $this->assertFileExists($this->tmpdir . '/tagmanifest-sha1.txt');

        $tmp = BagItUtils::tmpdir();
        new BagIt($tmp, false, false);
        $this->assertFalse(is_file($tmp . '/bag-info.txt'));
        $this->assertFalse(is_file($tmp . '/fetch.txt'));
        $this->assertFalse(is_file($tmp . '/tagmanifest-sha1.txt'));

        BagItUtils::rrmdir($tmp);
    }

    /**
     * Test enabling/disabling fetch processing in constructor.
     * @group BagIt
     * @covers \ScholarsLab\BagIt\BagItFetch::download
     */
    public function testConstructorFetch()
    {
        $tmp = BagItUtils::tmpdir();
        mkdir($tmp);
        file_put_contents(
            $tmp . "/fetch.txt",
            "http://www.google.com - google/index.html\n" .
            "http://www.yahoo.com - yahoo/index.html\n"
        );
        $bag = new BagIt($tmp, false, true, false);
        $this->assertFalse(
            is_file($bag->getDataDirectory() . '/google/index.html')
        );
        $this->assertFalse(
            is_file($bag->getDataDirectory() . '/yahoo/index.html')
        );

        BagItUtils::rrmdir($tmp);

        $tmp = BagItUtils::tmpdir();
        mkdir($tmp);
        file_put_contents(
            $tmp . "/fetch.txt",
            "http://www.google.com - data/google/index.html\n" .
            "http://www.yahoo.com - data/yahoo/index.html\n"
        );
        $bag = new BagIt($tmp, false, true, true);
        $this->assertFileExists($bag->getDataDirectory() . '/google/index.html');
        $this->assertFileExists($bag->getDataDirectory() . '/yahoo/index.html');

        BagItUtils::rrmdir($tmp);
    }

    /**
     * Test invalid version number.
     * @group BagIt
     * @covers ::isValid
     */
    public function testConstructorInvalidBagitFile()
    {
        $this->assertEquals(0, $this->bag->getBagInfo()['version_parts']['major']);

        $tmp = BagItUtils::tmpdir();
        mkdir($tmp);
        file_put_contents(
            $tmp . "/bagit.txt",
            "BagIt-Version: a.b\n" .
            "Tag-File-Character-Encoding: ISO-8859-1\n"
        );
        $bag = new BagIt($tmp);
        $this->assertFalse($bag->isValid());
        $bagErrors = $bag->getBagErrors();
        $this->assertTrue(BagItUtils::seenAtKey($bagErrors, 0, 'bagit'));
        BagItUtils::rrmdir($tmp);
    }

    /**
     * @param \ScholarsLab\BagIt\BagIt $bag
     */
    private function verifySampleBag(BagIt $bag)
    {
        $this->assertTrue($bag->isValid());

        // Testing what's in the bag (relativize the paths).
        $stripLen = strlen($bag->getBagDirectory()) + 1;
        $files = $bag->getBagContents();
        for ($i=0, $lsLen=count($files); $i<$lsLen; $i++) {
            $files[$i] = substr($files[$i], $stripLen);
        }
        $this->assertContains('data/imgs/109x109xcoins1-150x150.jpg', $files);
        $this->assertContains('data/imgs/109x109xprosody.png', $files);
        $this->assertContains('data/imgs/110x108xmetaphor1.png', $files);
        $this->assertContains('data/imgs/fellows1-150x150.png', $files);
        $this->assertContains('data/imgs/fibtriangle-110x110.jpg', $files);
        $this->assertContains('data/imgs/uvalib.png', $files);
        $this->assertContains('data/README.txt', $files);

        // Testing the checksums.
        $this->assertEquals(
            '547b21e9c710f562d448a6cd7d32f8257b04e561',
            $bag->getManifests()['sha1']->getData()['data/imgs/109x109xcoins1-150x150.jpg']
        );
        $this->assertEquals(
            'fba552acae866d24fb143fef0ddb24efc49b097a',
            $bag->getManifests()['sha1']->getData()['data/imgs/109x109xprosody.png']
        );
        $this->assertEquals(
            '4beed314513ad81e1f5fad42672a3b1bd3a018ea',
            $bag->getManifests()['sha1']->getData()['data/imgs/110x108xmetaphor1.png']
        );
        $this->assertEquals(
            '4372383348c55775966bb1deeeb2b758b197e2a1',
            $bag->getManifests()['sha1']->getData()['data/imgs/fellows1-150x150.png']
        );
        $this->assertEquals(
            'b8593e2b3c2fa3756d2b206a90c7259967ff6650',
            $bag->getManifests()['sha1']->getData()['data/imgs/fibtriangle-110x110.jpg']
        );
        $this->assertEquals(
            'aec60202453733a976433833c9d408a449f136b3',
            $bag->getManifests()['sha1']->getData()['data/imgs/uvalib.png']
        );
        $this->assertEquals(
            '0de174b95ebacc2d91b0839cb2874b2e8f604b98',
            $bag->getManifests()['sha1']->getData()['data/README.txt']
        );

        // Testing the fetch file.
        $data = $bag->getFetch()->getData();
        $this->assertEquals('http://www.scholarslab.org', $data[0]['url']);
        $this->assertEquals('data/index.html', $data[0]['filename']);
    }

    /**
     * Test constructing from a directory.
     * @group BagIt
     * @covers ::__construct
     */
    public function testConstructorDir()
    {
        $bagDir = __DIR__ . '/TestBag';
        $bag = new BagIt($bagDir);

        $this->assertFalse($bag->isCompressed());
        $this->verifySampleBag($bag);
    }

    /**
     * Test constructing from a zip file.
     * @group BagIt
     * @covers ::__construct
     */
    public function testConstructorZip()
    {
        $bagZip = __DIR__ . '/TestBag.zip';
        $bag = new BagIt($bagZip);

        $this->assertEquals('zip', $bag->getCompressionType());
        $this->verifySampleBag($bag);
    }

    /**
     * Test constructing from a tar.gz file.
     * @group BagIt
     * @covers ::__construct
     */
    public function testConstructorTGz()
    {
        $bagTar = __DIR__ . '/TestBag.tgz';
        $bag = new BagIt($bagTar);

        $this->assertEquals('tgz', $bag->getCompressionType());
        $this->verifySampleBag($bag);
    }

    /**
     * Test base bag is valid.
     * @group BagIt
     * @covers ::isValid
     */
    public function testIsValid()
    {
        $this->assertTrue($this->bag->isValid());
    }

    /**
     * Test base bag is extended and we can create one without the extension.
     * @group BagIt
     */
    public function testIsExtended()
    {
        $this->assertTrue($this->bag->isExtended());

        $tmp = BagItUtils::tmpdir();
        $bag = new BagIt($tmp, false, false);
        $this->assertFalse($bag->isExtended());

        BagItUtils::rrmdir($tmp);

        $tmp = BagItUtils::tmpdir();
        mkdir($tmp);
        file_put_contents(
            $tmp . "/bag-info.txt",
            "Source-organization: University of Virginia Alderman Library\n" .
            "Contact-name: Eric Rochester\n" .
            "Bag-size: very, very small\n"
        );
        $bag = new BagIt($tmp, false, false);
        $this->assertFalse($bag->isExtended());

        BagItUtils::rrmdir($tmp);
    }

    /**
     * Test default bag-info information.
     * @group BagIt
     * @covers ::getBagInfo
     */
    public function testGetBagInfo()
    {
        $bagInfo = $this->bag->getBagInfo();

        $this->assertInternalType('array', $bagInfo);

        $this->assertArrayHasKey('version', $bagInfo);
        $this->assertArrayHasKey('encoding', $bagInfo);
        $this->assertArrayHasKey('hash', $bagInfo);

        $this->assertEquals('0.96', $bagInfo['version']);
        $this->assertEquals('UTF-8', $bagInfo['encoding']);
        $this->assertEquals('sha1', $bagInfo['hash']);
    }

    /**
     * Test the data directory value.
     * @group BagIt
     * @covers ::getDataDirectory
     */
    public function testGetDataDirectory()
    {
        $dataDir = $this->bag->getDataDirectory();
        $this->assertStringStartsWith($this->tmpdir, $dataDir);
    }

    /**
     * Test default hash encoding.
     * @group BagIt
     * @covers ::getHashEncodings
     */
    public function testGetHashEncoding()
    {
        $hash = $this->bag->getHashEncodings();
        $this->assertEquals(array('sha1'), $hash);
    }

    /**
     * Test we don't add multiple of the same encodings.
     * @group BagIt
     * @covers ::getHashEncodings
     * @covers ::addHashEncoding
     */
    public function testDeduplicateHashEncodings()
    {
        $hash = $this->bag->getHashEncodings();
        $this->assertEquals(array('sha1'), $hash);
        $this->bag->addHashEncoding('sha1');
        $this->assertEquals(array('sha1'), $hash);
    }

    /**
     * Utility to test adding a second hash and removing sha1.
     *
     * @param string $hash The hash to add.
     *
     * @throws \ScholarsLab\BagIt\BagItException If you try to remove the only hash encoding.
     */
    private function verifyAddingHashEncodingToDefault($hash)
    {
        $this->bag->addHashEncoding($hash);
        $this->assertEquals(array(), array_diff(array('sha1', $hash), $this->bag->getHashEncodings()));
        $this->bag->removeHashEncoding('sha1');
        $this->assertEquals(array($hash), $this->bag->getHashEncodings());
        // Reset hash encodings
        $this->bag->addHashEncoding('sha1');
        $this->bag->removeHashEncoding($hash);
    }

    /**
     * Test the MUST support algorithms.
     * @group BagIt
     * @covers ::addHashEncoding
     * @covers ::removeHashEncoding
     */
    public function testRequiredHashEncodings()
    {
        $this->verifyAddingHashEncodingToDefault('sha256');
        $this->verifyAddingHashEncodingToDefault('sha512');
    }

    /**
     * Test all other supported hash encodings.
     * @group BagIt
     * @covers ::addHashEncoding
     * @covers ::removeHashEncoding
     */
    public function testSetOtherHashEncoding()
    {
        // We only want to test non
        $nonSha1Algos = array_diff($this->validHashAlgos, array('sha1'));
        foreach ($nonSha1Algos as $hash) {
            $this->verifyAddingHashEncodingToDefault($hash);
        }
    }

    /**
     * Test adding all possible hash encodings.
     * @group BagIt
     * @covers ::addHashEncoding
     * @covers ::removeHashEncoding
     */
    public function testAddAllHashEncodings()
    {
        foreach ($this->validHashAlgos as $hash) {
            $this->bag->addHashEncoding($hash);
        }
        $this->assertEquals(array(), array_diff($this->validHashAlgos, $this->bag->getHashEncodings()));
    }


    /**
     * Test adding an invalid hash encoding algorithm.
     * @group BagIt
     * @expectedException \InvalidArgumentException
     * @covers ::addHashEncoding
     */
    public function testSetHashEncodingERR()
    {
        $this->bag->addHashEncoding('err');
    }

    /**
     * Ensure in an extended bag both manifest and tagmanifest are set.
     * @group BagIt
     * @covers ::addHashEncoding
     */
    public function testSetHashEncodingBoth()
    {
        $this->bag->addHashEncoding('md5');
        $this->assertEquals('md5', $this->bag->getManifests()['md5']->getHashEncoding());
        $this->assertEquals('md5', $this->bag->getTagManifests()['md5']->getHashEncoding());
    }

    /**
     * Ensure we can't remove the last hash encoding.
     *
     * @group BagIt
     * @covers ::removeHashEncoding
     * @expectedException \ScholarsLab\BagIt\BagItException
     */
    public function testRemoveLastHashEncoding()
    {
        $this->assertEquals(array('sha1'), $this->bag->getHashEncodings());
        $this->bag->removeHashEncoding('sha1');
    }

    /**
     * Test bag contents methods.
     *
     * @group BagIt
     * @covers ::getBagContents
     */
    public function testGetBagContents()
    {
        $bagContents = $this->bag->getBagContents();

        $this->assertInternalType('array', $bagContents);
        $this->assertEquals(0, count($bagContents));

        $tmp = BagItUtils::tmpdir();
        mkdir($tmp);
        mkdir("$tmp/data");
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

        BagItUtils::rrmdir($tmp);
    }

    /**
     * Test bag does not validate without data directory.
     * @group BagIt
     * @covers ::getBagErrors
     */
    public function testGetBagErrors()
    {
        $bagErrors = $this->bag->getBagErrors();
        $this->assertInternalType('array', $bagErrors);
        $this->assertCount(0, $bagErrors);

        BagItUtils::rrmdir($this->bag->getDataDirectory());
        $this->bag->validate();
        $this->assertGreaterThan(0, count($this->bag->getBagErrors()));
    }

    /**
     * Test bag does not validate without data directory.
     *
     * @see testGetBagErrors()
     * @group BagIt
     * @covers ::getBagErrors
     */
    public function testGetBagErrorsValidate()
    {
        BagItUtils::rrmdir($this->bag->getDataDirectory());
        $bagErrors = $this->bag->getBagErrors(true);
        $this->assertInternalType('array', $bagErrors);
        $this->assertGreaterThan(0, count($bagErrors));
    }

    /**
     * Test bag does not validate without bagit.txt
     * @group BagIt
     * @covers ::validate
     */
    public function testValidateMissingBagFile()
    {
        unlink($this->tmpdir . '/bagit.txt');

        $this->bag->validate();
        $bagErrors = $this->bag->getBagErrors();

        $this->assertFalse($this->bag->isValid());
        $this->assertTrue(BagItUtils::seenAtKey($bagErrors, 0, 'bagit.txt'));
    }

    /**
     * Test bag does not validate with invalid checksum.
     * @group BagIt
     * @covers ::validate
     */
    public function testValidateChecksum()
    {
        $tmp = BagItUtils::tmpdir();
        mkdir($tmp);
        file_put_contents(
            $tmp . "/manifest-sha1.txt",
            "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa data/missing.txt\n"
        );
        mkdir($tmp . '/data');
        touch($tmp . '/data/missing.txt');
        $bag = new BagIt($tmp);
        $bag->validate();
        $bagErrors = $bag->getBagErrors();

        $this->assertFalse($bag->isValid());
        $this->assertTrue(BagItUtils::seenAtKey($bagErrors, 0, 'data/missing.txt'));
        $this->assertTrue(BagItUtils::seenAtKey($bagErrors, 1, 'Checksum mismatch.'));

        BagItUtils::rrmdir($tmp);
    }

    /**
     * Test that update() creates missing files.
     * @group BagIt
     * @covers ::update
     */
    public function testUpdateCreateMissing()
    {
        $tmp = BagItUtils::tmpdir();

        $this->assertFileNotExists($tmp . '/bagit.txt');
        $this->assertFileNotExists($tmp . '/manifest-sha1.txt');
        $this->assertFileNotExists($tmp . '/data');

        $bag = new BagIt($tmp);
        $bag->update();

        $this->assertFileExists($tmp . '/bagit.txt');
        $this->assertFileExists($tmp . '/manifest-sha1.txt');
        $this->assertTrue(is_dir($tmp . '/data'));

        BagItUtils::rrmdir($tmp);
    }

    /**
     * Test data filenames are sanitized.
     * @group BagIt
     * @covers ::update
     */
    public function testUpdateSanitize()
    {
        $tmp = BagItUtils::tmpdir();
        mkdir($tmp);
        mkdir($tmp . '/data');
        touch($tmp . '/data/has space');
        touch($tmp . '/data/PRN');
        touch($tmp . '/data/backup~');
        touch($tmp . '/data/.hidden');
        touch($tmp . '/data/quoted "yep" quoted');

        $bag = new BagIt($tmp);
        $bag->update();

        $this->assertFileNotExists($tmp . '/data/has space');
        $this->assertFileExists($tmp . '/data/has_space');

        $this->assertFalse(is_file($tmp . '/data/PRN'));
        $this->assertEquals(1, count(glob($tmp . '/data/prn_*')));

        $this->assertFalse(is_file($tmp . '/data/backup~'));

        $this->assertFalse(is_file($tmp . '/data/quoted "yep" quoted'));
        $this->assertFileExists($tmp . '/data/quoted_yep_quoted');

        BagItUtils::rrmdir($tmp);
    }

    /**
     * Test update() updates checksums.
     * @group BagIt
     * @covers ::update
     */
    public function testUpdateChecksums()
    {
        $tmp = BagItUtils::tmpdir();
        mkdir($tmp);
        file_put_contents(
            $tmp . "/manifest-sha1.txt",
            "abababababababababababababababababababab data/missing.txt\n"
        );
        mkdir($tmp . '/data');
        file_put_contents(
            $tmp . '/data/missing.txt',
            "This space intentionally left blank.\n"
        );
        $bag = new BagIt($tmp);
        $bag->update();

        $this->assertEquals(
            "a5c44171ca6618c6ee24c3f3f3019df8df09a2e0 data/missing.txt\n",
            file_get_contents($tmp . '/manifest-sha1.txt')
        );
        $this->assertEquals(
            'a5c44171ca6618c6ee24c3f3f3019df8df09a2e0',
            $bag->getManifests()['sha1']->getData()['data/missing.txt']
        );

        BagItUtils::rrmdir($tmp);
    }

    /**
     * Test update() creates checksums where missing.
     * @group BagIt
     * @covers ::update
     */
    public function testUpdateNewFiles()
    {
        $tmp = BagItUtils::tmpdir();
        mkdir($tmp);
        mkdir($tmp . '/data');
        file_put_contents(
            $tmp . '/data/missing.txt',
            "This space intentionally left blank.\n"
        );
        $bag = new BagIt($tmp);
        $bag->update();

        $this->assertEquals(
            "a5c44171ca6618c6ee24c3f3f3019df8df09a2e0 data/missing.txt\n",
            file_get_contents($tmp . '/manifest-sha1.txt')
        );
        $this->assertEquals(
            'a5c44171ca6618c6ee24c3f3f3019df8df09a2e0',
            $bag->getManifests()['sha1']->getData()['data/missing.txt']
        );

        BagItUtils::rrmdir($tmp);
    }

    /**
     * Test checksums from removed files are removed from manifest.
     * @group BagIt
     * @covers ::update
     */
    public function testUpdateDeletedFiles()
    {
        $tmp = BagItUtils::tmpdir();
        mkdir($tmp);
        file_put_contents(
            $tmp . "/manifest-sha1.txt",
            "abcdabcdabcdabcdabcdabcdabcdabcdabcdabcd data/missing.txt\n"
        );
        mkdir($tmp . '/data');
        $bag = new BagIt($tmp);
        $bag->update();

        $this->assertEquals(
            '',
            file_get_contents($tmp . '/manifest-sha1.txt')
        );
        $this->assertFalse(
            array_key_exists('data/missing.txt', $bag->getManifests()['sha1']->getData())
        );

        BagItUtils::rrmdir($tmp);
    }

    /**
     * Test update() creates missing extended bag files.
     * @group BagIt
     * @covers ::update
     */
    public function testUpdateExtended()
    {
        $tmp = BagItUtils::tmpdir();

        $this->assertFileNotExists($tmp . '/bag-info.txt');
        $this->assertFileNotExists($tmp . '/tagmanifest-sha1.txt');
        $this->assertFileNotExists($tmp . '/fetch.txt');

        $bag = new BagIt($tmp);
        $bag->update();

        $this->assertFileExists($tmp . '/bag-info.txt');
        $this->assertFileExists($tmp . '/tagmanifest-sha1.txt');
        $this->assertFileNotExists($tmp . '/fetch.txt');

        BagItUtils::rrmdir($tmp);
    }

    /**
     * Test adding a file copies it correctly to data directory when data in destination.
     * @group BagIt
     * @covers ::addFile
     */
    public function testAddFile()
    {
        $srcdir = __DIR__ . '/TestBag/data';

        $this->bag->addFile("$srcdir/README.txt", 'data/README.txt');

        $datadir = $this->bag->getDataDirectory();
        $this->assertFileExists("{$datadir}/README.txt");
        $this->assertFileEquals("$srcdir/README.txt", "{$datadir}/README.txt");

        $this->bag->addFile("$srcdir/imgs/uvalib.png", "data/pics/uvalib.png");

        $this->assertFileExists("{$datadir}/pics/uvalib.png");
        $this->assertFileEquals(
            "$srcdir/imgs/uvalib.png",
            "{$datadir}/pics/uvalib.png"
        );
    }

    /**
     * Test adding a file copies it correctly to data directory when data not in destination.
     * @group BagIt
     * @covers ::addFile
     */
    public function testAddFileAddDataDir()
    {
        $srcdir = __DIR__ . '/TestBag/data';

        $this->bag->addFile("$srcdir/README.txt", 'README.txt');

        $datadir = $this->bag->getDataDirectory();
        $this->assertFileExists("{$datadir}/README.txt");
        $this->assertFileEquals("$srcdir/README.txt", "{$datadir}/README.txt");

        $this->bag->addFile("$srcdir/imgs/uvalib.png", "pics/uvalib.png");

        $this->assertFileExists("{$datadir}/pics/uvalib.png");
        $this->assertFileEquals(
            "$srcdir/imgs/uvalib.png",
            "{$datadir}/pics/uvalib.png"
        );
    }

    /**
     * Test creating a file with content into data/ directory.
     * @group BagIt
     * @covers ::createFile
     */
    public function testCreateFile()
    {
        $testContent = "This is some test content.";

        $this->bag->createFile($testContent, "data/testCreateFile.txt");
        $datadir = $this->bag->getDataDirectory();
        $this->assertFileExists("{$datadir}/testCreateFile.txt");
        $content = file_get_contents("{$datadir}/testCreateFile.txt");
        $this->assertEquals($content, $testContent);
    }

    /**
     * Test creating a file with content into root directory which goes to data/
     * @group BagIt
     * @covers ::createFile
     */
    public function testCreateFileAddDataDir()
    {
        $testContent = "This is some test content.";

        $this->bag->createFile($testContent, "testCreateFile.txt");
        $datadir = $this->bag->getDataDirectory();
        $this->assertFileExists("{$datadir}/testCreateFile.txt");
        $content = file_get_contents("{$datadir}/testCreateFile.txt");
        $this->assertEquals($content, $testContent);
    }

    /**
     * Test attempting to create the same file twice.
     * @group BagIt
     * @expectedException \ScholarsLab\BagIt\BagItException
     * @covers ::createFile
     */
    public function testCreateFileDuplicate()
    {
        $testContent = "This is some test content.";

        $this->bag->createFile($testContent, "testCreateFile.txt");
        $this->bag->createFile('', "testCreateFile.txt");
    }



    /**
     * Test attempting to copy a non-existant file into the bag.
     * This is expected to throw a warning from the copy() method.
     * @group BagIt
     * @expectedException \PHPUnit_Framework_Error_Warning
     * @covers ::addFile
     */
    public function testAddFileMissing()
    {
        $srcdir = __DIR__ . '/TestBag/data';
        $this->bag->addFile("$srcdir/missing.txt", 'data/missing.txt');
    }

    /**
     * Utility function to create a bag, package it with compressionType, open
     * the bag and validate it against the original.
     *
     * @param string $compressionType compression type, one of zip, tgz
     * @throws \ErrorException
     * @throws \ScholarsLab\BagIt\BagItException
     */
    private function verifyBagCreatePackageAndValidate($compressionType)
    {
        $tmp = BagItUtils::tmpdir();
        $dirName = basename($tmp);

        mkdir($tmp);
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

        $packageTmp = BagItUtils::tmpdir();
        mkdir($packageTmp);
        $zippath1 = "{$packageTmp}/{$dirName}.{$compressionType}";

        $bag->package($zippath1, $compressionType);
        $this->assertFileExists($zippath1);

        $bag1 = new BagIt($zippath1);
        $this->assertTrue($bag1->isCompressed());
        $this->assertEquals($compressionType, $bag1->getCompressionType());
        $this->assertFileExists($bag1->getDataDirectory() . '/missing.txt');
        $this->assertFileExists($bag1->getDataDirectory() . "/../fetch.txt");
        $this->assertEquals(
            file_get_contents($tmp . '/data/missing.txt'),
            file_get_contents($bag1->getDataDirectory() . '/missing.txt')
        );
        $this->assertEquals(
            file_get_contents($tmp . '/fetch.txt'),
            file_get_contents($bag1->getDataDirectory() . '/../fetch.txt')
        );
        $this->assertTrue($bag1->isValid());

        BagItUtils::rrmdir($tmp);
        BagItUtils::rrmdir($packageTmp);
        BagItUtils::rrmdir($zippath1);
    }

    /**
     * Create a bag package it as a zip. Then open the bag and validate it.
     * @group BagIt
     * @covers ::package
     */
    public function testPackageZip()
    {
        $this->verifyBagCreatePackageAndValidate('zip');
    }

    /**
     * Create a bag package it as a tgz. Then open the bag and validate it.
     * @group BagIt
     * @covers ::package
     */
    public function testPackageTGz()
    {
        $this->verifyBagCreatePackageAndValidate('tgz');
    }

    /**
     * Test the default configuration?
     * @group BagIt
     */
    public function testEmptyDirectory()
    {
        $tmp = BagItUtils::tmpdir();
        mkdir($tmp);

        new BagIt($tmp);
        $this->assertFileExists("$tmp/bagit.txt");
        $this->assertFileExists("$tmp/manifest-sha1.txt");
        $this->assertFileExists("$tmp/bag-info.txt");
        $this->assertFileNotExists("$tmp/fetch.txt");
        $this->assertFileExists("$tmp/tagmanifest-sha1.txt");

        BagItUtils::rrmdir($tmp);
    }
}
