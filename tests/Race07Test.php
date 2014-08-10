<?php
use Simresults\Data_Reader_Race07;
use Simresults\Data_Reader;
use Simresults\Session;
use Simresults\Participant;

/**
 * Tests for the Race07 reader
 *
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class Race07Test extends PHPUnit_Framework_TestCase {

    /**
     * Set error reporting
     *
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        error_reporting(E_ALL);
    }


    /**
     * Test exception when no data is supplied
     *
     * @expectedException Simresults\Exception\CannotReadData
     */
    public function testCreatingNewRace07ReaderWithInvalidData()
    {
        $reader = new Data_Reader_Race07('Unknown data for reader');
    }


    /***
    **** Simple tests that do not fit in the full race log used for testing.
    **** Most of the below tests are done on modfied files
    ***/


    /**
     * Test non-zero based logs on laps. Found on F1 challenge log files
     */
    public function testNonZeroBasedLaps()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.'/logs/race07/prosracing Clio Cup_2013_02_12_22_06_19_Race2_changed_lap_numbers.txt');

        // Get the data reader for the given data source
        $reader = Data_Reader::factory($file_path);

        // Get session
        $session = $reader->getSession();

        // Get participant "flashdepau"
        $participants = $session->getParticipants();
        $laps = $participants[1]->getLaps();

        // Validate using time, to prevent any false positives due to number
        // fixes
        $this->assertSame(147.888, $laps[0]->getTime());
    }



    /***
    **** Below tests use a full valid race log file
    ***/


    /**
     * Test reading the session
     */
    public function testReadingSession()
    {
        // Get session
        $session = $this->getWorkingReader()->getSession();

        // Get session date
        $date = $session->getDate();

        // Validate timestamp of date
        $this->assertSame(1360706779, $date->getTimestamp());

        // Test default timezone (UTC)
        $this->assertSame('2013-02-12 22:06:19', $date->format('Y-m-d H:i:s'));
        $this->assertSame('UTC', $date->getTimezone()->getName());

        //-- Validate other
        $this->assertSame(Session::TYPE_RACE, $session->getType());
        $this->assertSame(12, $session->getLastedLaps());
    }

    /**
     * Test reading the game of a session
     */
    public function testReadingSessionGame()
    {
        // Get the game
        $game = $this->getWorkingReader()->getSession()->getGame();

        // Validate game
        $this->assertSame('RACE 07', $game->getName());
        $this->assertSame('1.2.1.10', $game->getVersion());
    }


    /**
     * Test reading the track of a session
     */
    public function testReadingSessionTrack()
    {
        // Get the track
        $track = $this->getWorkingReader()->getSession()->getTrack();

        // Validate track
        $this->assertSame('Monza_2007', $track->getVenue());
        $this->assertSame('2007_Monza', $track->getCourse());
        $this->assertSame(5782.6406, $track->getLength());
    }

    /**
     * Test reading the participants of a session
     */
    public function testReadingSessionParticipants()
    {
        // Get first participant (winner, slotXXX)
        $participants = $this->getWorkingReader()->getSession()
            ->getParticipants();
        $participant = $participants[0];

        $this->assertSame('zezette racing', $participant->getTeam());
        $this->assertSame('[PRG]Yozeze34',
                          $participant->getDriver()->getName());
        $this->assertSame('Renault Sport Clio CUP France 2008',
                          $participant->getVehicle()->getName());
        $this->assertSame(1, $participant->getPosition());
        $this->assertSame(Participant::FINISH_NORMAL,
            $participant->getFinishStatus());


        // TODO: DNF status etc

        // TODO:
        // // Get last participant
        // $participant = $participants[20];
        // $this->assertSame('Hugh Lemont',
        //                   $participant->getDriver()->getName());
        // $this->assertSame('lotus_exige_scura',
        //                   $participant->getVehicle()->getName());
        // $this->assertSame(12, $participant->getPosition());
        // $this->assertSame(Participant::FINISH_NORMAL,
        //     $participant->getFinishStatus());
    }

    /**
     * Test reading laps of participants
     *
     * TODO: Fix positions
     */
    public function testReadingLapsOfParticipants()
    {
        // Get participants
        $participants = $this->getWorkingReader()->getSession()
            ->getParticipants();

        // Get the laps of first participants
        $laps = $participants[0]->getLaps();

        // Validate we have 12 laps
        $this->assertSame(12, count($laps));

        // Get driver of first participant (only one cause there are no swaps)
        $driver = $participants[0]->getDriver();

        // Get first lap only
        $lap = $laps[0];

        // Validate laps
        $this->assertSame(1, $lap->getNumber());
        // TODO: FIX?
        $this->assertSame(null, $lap->getPosition());
        $this->assertSame(138.685, $lap->getTime());
        $this->assertSame(0, $lap->getElapsedSeconds());
        $this->assertSame($participants[0], $lap->getParticipant());
        $this->assertSame($driver, $lap->getDriver());

        // // Second lap
        $lap = $laps[1];
        $this->assertSame(2, $lap->getNumber());
        $this->assertSame(2, $lap->getPosition());
        $this->assertSame(126.276, $lap->getTime());
        $this->assertSame(128.404, $lap->getElapsedSeconds());


        // // Last lap
        $lap = $laps[11];
        $this->assertSame(12, $lap->getNumber());
        $this->assertSame(1, $lap->getPosition());
        $this->assertSame(131.264, $lap->getTime());
        $this->assertSame(1412.984, $lap->getElapsedSeconds());


        // // Validate extra positions
        $laps = $participants[3]->getLaps(); // totomms laps
        $this->assertNull($laps[0]->getPosition());
        $this->assertSame(5, $laps[2]->getPosition());
        $this->assertSame(7, $laps[4]->getPosition());
    }



    /**
     * Get a working reader
     */
    protected function getWorkingReader()
    {
        static $reader;

        // Reader aready created
        if ($reader)
        {
            return $reader;
        }

        // The path to the data source
        $file_path = realpath(__DIR__.'/logs/race07/prosracing Clio Cup_2013_02_12_22_06_19_Race2.txt');

        // Get the data reader for the given data source
        $reader = Data_Reader::factory($file_path);

        // Return reader
        return $reader;
    }
}