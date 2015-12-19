// html5media enables <video> and <audio> tags in all major browsers
// External File: http://api.html5media.info/1.1.8/html5media.min.js


// Add user agent as an attribute on the <html> tag...
// Inspiration: http://css-tricks.com/ie-10-specific-styles/
var b = document.documentElement;
b.setAttribute('data-useragent',  navigator.userAgent);
b.setAttribute('data-platform', navigator.platform );


// HTML5 audio player + playlist controls...
// Inspiration: http://jonhall.info/how_to/create_a_playlist_for_html5_audio
// Mythium Archive: https://archive.org/details/mythium/
jQuery(function ($) {
    var supportsAudio = !! document.createElement('audio').canPlayType;
    if (supportsAudio) {
        var index = 0,
            playing = false;
        mediaPath = 'music/',
        extension = '',
        tracks = [{
            "track": 1,
            "name": "Aranda - We Are the Enemy",
			"length": "03:39",
            "file": "Aranda - We Are the Enemy"
        }, {
            "track": 2,
            "name": "Art of Dying - Everything",
            "length": "03:55",
            "file": "Art of Dying - Everything"
        }, {
            "track": 3,
            "name": "Asking Alexandria - I Won't Give In",
            "length": "03:51",
            "file": "Asking Alexandria - I Won't Give In"
        }, {
            "track": 4,
            "name": "Atreyu - Do You Know Who You Are",
            "length": "03:51",
            "file": "Atreyu - Do You Know Who You Are"
        }, {
            "track": 5,
            "name": "AWOLNATION - I Am",
            "length": "04:34",
            "file": "AWOLNATION - I Am"
        }, {
            "track": 6,
            "name": "Breaking Benjamin - Angels Fall",
            "length": "03:49",
            "file": "Breaking Benjamin - Angels Fall"
        }, {
            "track": 7,
            "name": "Bridge to Grace - Everything",
            "length": "04:15",
            "file": "Bridge to Grace - Everything"
        }, {
            "track": 8,
            "name": "Bring Me the Horizon - Throne",
            "length": "03:12",
            "file": "Bring Me the Horizon - Throne"
        }, {
            "track": 9,
            "name": "Chris Cornell - Nearly Forgot My Broken Heart",
            "length": "03:54",
            "file": "Chris Cornell - Nearly Forgot My Broken Heart"
        }, {
            "track": 10,
            "name": "Disturbed - The Light",
            "length": "04:17",
            "file": "Disturbed - The Light"
        }, {
            "track": 11,
            "name": "Disturbed - The Vengeful One",
            "length": "04:12",
            "file": "Disturbed - The Vengeful One"
        }, {
            "track": 12,
            "name": "Five Finger Death Punch - Jekyll and Hyde",
            "length": "03:27",
            "file": "Five Finger Death Punch - Jekyll and Hyde"
        }, {
            "track": 13,
            "name": "Five Finger Death Punch - Wash It All Away",
            "length": "03:45",
            "file": "Five Finger Death Punch - Wash It All Away"
        }, {
            "track": 14,
            "name": "Foo Fighters - Outside",
            "length": "05:15",
            "file": "Foo Fighters - Outside"
        }, {
            "track": 15,
            "name": "From Ashes to New - Through It All",
            "length": "03:33",
            "file": "From Ashes to New - Through It All"
        }, {
            "track": 16,
            "name": "Ghost - Cirice",
            "length": "06:02",
            "file": "Ghost - Cirice"
        }, {
            "track": 17,
            "name": "Halestorm - I Am the Fire",
            "length": "03:37",
            "file": "Halestorm - I Am the Fire"
        }, {
            "track": 18,
            "name": "Highly Suspect - Bloodfeather",
            "length": "03:54",
            "file": "Highly Suspect - Bloodfeather"
        }, {
            "track": 19,
            "name": "In This Moment - Big Bad Wolf",
            "length": "05:11",
            "file": "In This Moment - Big Bad Wolf"
        }, {
            "track": 20,
            "name": "Islander - Cold Speak",
            "length": "03:10",
            "file": "Islander - Cold Speak"
        }, {
            "track": 21,
            "name": "Korn - Never Never",
            "length": "03:41",
            "file": "Korn - Never Never"
        }, {
            "track": 22,
            "name": "Like a Storm - Become the Enemy",
            "length": "03:49",
            "file": "Like a Storm - Become the Enemy"
        }, {
            "track": 23,
            "name": "Mumford and Sons - The Wolf",
            "length": "03:41",
            "file": "Mumford and Sons - The Wolf"
        }, {
            "track": 24,
            "name": "Nathaniel Rateliff and the Night Sweats - S.O.B",
            "length": "04:08",
            "file": "Nathaniel Rateliff and the Night Sweats - S.O.B"
        }, {
            "track": 25,
            "name": "Nothing More - Here's to the Heartache",
            "length": "04:17",
            "file": "Nothing More - Here's to the Heartache"
        }, {
            "track": 26,
            "name": "Papa Roach - Gravity (feat. Maria Brink of In This Moment)",
            "length": "04:05",
            "file": "Papa Roach - Gravity (feat. Maria Brink of In This Moment)"
        }, {
            "track": 27,
            "name": "Parkway Drive - Vice Grip",
            "length": "04:24",
            "file": "Parkway Drive - Vice Grip"
        }, {
            "track": 28,
            "name": "Pop Evil - Footsteps",
            "length": "04:22",
            "file": "Pop Evil - Footsteps"
        }, {
            "track": 29,
            "name": "RED SUN RISING - The Otherside",
            "length": "03:36",
            "file": "RED SUN RISING - The Otherside"
        }, {
            "track": 30,
            "name": "Seether - Save Today",
            "length": "04:48",
            "file": "Seether - Save Today"
        }, {
            "track": 31,
            "name": "Sevendust - Thank You",
            "length": "04:27",
            "file": "Sevendust - Thank You"
        }, {
            "track": 32,
            "name": "Shinedown - Cut the Cord",
            "length": "03:44",
            "file": "Shinedown - Cut the Cord"
        }, {
            "track": 33,
            "name": "Shinedown - State of My Head",
            "length": "03:26",
            "file": "Shinedown - State of My Head"
        }, {
            "track": 34,
            "name": "The Arcs - Outta My Mind",
            "length": "03:35",
            "file": "The Arcs - Outta My Mind"
        }, {
            "track": 35,
            "name": "Theory of a Deadman - Blow",
            "length": "03:36",
            "file": "Theory of a Deadman - Blow"
        }, {
            "track": 36,
            "name": "Three Days Grace - Fallen Angel",
            "length": "03:06",
            "file": "Three Days Grace - Fallen Angel"
		}, {
            "track": 37,
            "name": "Trapt - Passenger",
            "length": "03:49",
            "file": "TTrapt - Passenger"
		}, {
            "track": 38,
            "name": "Trivium - Until the World Goes Cold",
            "length": "05:21",
            "file": "Trivium - Until the World Goes Cold"
		}, {
            "track": 39,
            "name": "Turbowolf - Rabbits Foot",
            "length": "02:44",
            "file": "Turbowolf - Rabbits Foot"
		}, {
            "track": 40,
            "name": "We Are Harlot - Someday",
            "length": "04:06",
            "file": "We Are Harlot - Someday"
		}, {
            "track": 41,
            "name": "Wilson - Right To Rise",
            "length": "03:54",
            "file": "Wilson - Right To Rise"
        }],
        trackCount = tracks.length,
        npAction = $('#npAction'),
        npTitle = $('#npTitle'),
        audio = $('#audio1').bind('play', function () {
            playing = true;
            npAction.text('Now Playing...');
        }).bind('pause', function () {
            playing = false;
            npAction.text('Paused...');
        }).bind('ended', function () {
            npAction.text('Paused...');
            if ((index + 1) < trackCount) {
                index++;
                loadTrack(index);
                audio.play();
            } else {
                audio.pause();
                index = 0;
                loadTrack(index);
            }
        }).get(0),
        btnPrev = $('#btnPrev').click(function () {
            if ((index - 1) > -1) {
                index--;
                loadTrack(index);
                if (playing) {
                    audio.play();
                }
            } else {
                audio.pause();
                index = 0;
                loadTrack(index);
            }
        }),
        btnNext = $('#btnNext').click(function () {
            if ((index + 1) < trackCount) {
                index++;
                loadTrack(index);
                if (playing) {
                    audio.play();
                }
            } else {
                audio.pause();
                index = 0;
                loadTrack(index);
            }
        }),
        li = $('#plList li').click(function () {
            var id = parseInt($(this).index());
            if (id !== index) {
                playTrack(id);
            }
        }),
        loadTrack = function (id) {
            $('.plSel').removeClass('plSel');
            $('#plList li:eq(' + id + ')').addClass('plSel');
            npTitle.text(tracks[id].name);
            index = id;
            audio.src = mediaPath + tracks[id].file + extension;
        },
        playTrack = function (id) {
            loadTrack(id);
            audio.play();
        };
        extension = audio.canPlayType('audio/mpeg') ? '.mp3' : audio.canPlayType('audio/ogg') ? '.ogg' : '';
        loadTrack(index);
    }
});