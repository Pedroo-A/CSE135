(function() {
    //session ID generator
    function getSessionId() {
        let sid = sessionStorage.getItem('wrecked_tech_session_id');
        if (!sid) {
            //generate a random UUID if one doesn't exist.
            sid = self.crypto.randomUUID ? self.crypto.randomUUID() : Math.random().toString(36).substring(2);
            sessionStorage.setItem('wrecked_tech_session_id', sid);
        }
        return sid;
    }

    const ENDPOINT_URL = 'https://collector.csepedro.site/logger.php'; 
    
    let analyticsData = {
        sessionId: getSessionId(),
        static: {},
        performance: {},
        activity: {
            errors: [],
            mouseMovements: [],
            clicks: [],
            scrolling: [],
            keyStrokes: [],
            idleBreaks: [],
            enteredPageAt: Date.now(),
            leftPageAt: null,
            pageUrl: window.location.href
        }
    };


    //STATIC DATA COLLECTION
    function collectStaticData() {
        //collect the basics 
        analyticsData.static.userAgent = navigator.userAgent;
        analyticsData.static.language = navigator.language;
        analyticsData.static.cookiesEnabled = navigator.cookieEnabled;
        
        analyticsData.static.jsEnabled = true; 

        //get user screen and window dimensions
        analyticsData.static.screenWidth = window.screen.width;
        analyticsData.static.screenHeight = window.screen.height;
        analyticsData.static.windowWidth = window.innerWidth;
        analyticsData.static.windowHeight = window.innerHeight;
        
        // check the network
        if ('connection' in navigator){
            const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
            analyticsData.static.networkType = connection.effectiveType || 'unknown';
        } 
        

        // checking for css
        const style = document.createElement('style');
        style.innerHTML = '#css-test{height: 1px;}';
        document.head.appendChild(style);
        const testDiv = document.createElement('div');
        testDiv.id = 'css-test';
        document.body.appendChild(testDiv);
        analyticsData.static.cssEnabled = getComputedStyle(testDiv).height === '1px';
        document.head.removeChild(style);
        document.body.removeChild(testDiv);

        const img = new Image();
        img.onload = () => { analyticsData.static.imagesEnabled = true; };
        img.onerror = () => { analyticsData.static.imagesEnabled = false; };
        img.src = "data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"; //Asked AI to generate a random 1x1 base64 encoded image for this part
    }


    //PERFORMANCE DATA COLLECTION
    function collectPerformanceData() {
        console.log("Collecting data");
        //Adding a small timeout bc i kept running into issues
        setTimeout(() => {
            //get the whole timing obj
            const navTiming = performance.getEntriesByType('navigation')[0];

            if (navTiming) {
                const startLoadTime = performance.timeOrigin;
                const endLoadTime = performance.timeOrigin + navTiming.loadEventEnd;
                const totalLoadTime = endLoadTime - startLoadTime;

                // Store in object
                analyticsData.performance = {
                    wholeTimingObject: navTiming,
                    startLoadTime: startLoadTime,
                    endLoadTime: endLoadTime,
                    totalLoadTime: totalLoadTime 
                };

                console.log("Performance Data:", analyticsData.performance);
                sendData(analyticsData);
            }
        }, 100);
    }

    // ACTIVITY DATA COLLECTION
    let idleTimer;
    let isIdle = false;
    let idleStartTime = Date.now();
    
    //record idkle time
    function resetIdle() {
        const now = Date.now();
        if (isIdle) {
            const idleDuration = now - idleStartTime;
            if (idleDuration >= 2000) {
                analyticsData.activity.idleBreaks.push({
                    breakEnded: now,
                    duration: idleDuration
                });
            }
            isIdle = false;
        }
        
        //reset idle timer
        clearTimeout(idleTimer);
        idleTimer = setTimeout(() => {
            isIdle = true;
            idleStartTime = Date.now();
        }, 2000); 
    }

    //throttle to not overwhelm
    function throttle(fn, delay) {
        let lastCall = 0;
        return function(...args) {
            const now = Date.now();
            if (now - lastCall >= delay) {
                lastCall = now;
                return fn(...args);
            }
        };
    }

    function setupActivityListeners() {
        //record entrance time and URL
        analyticsData.activity.enteredPageAt = Date.now();
        analyticsData.activity.pageUrl = window.location.href;

        //Errors
        window.onerror = (message, source, linen, coln, error) => {
            analyticsData.activity.errors.push({message, source, linen, coln, timestamp: Date.now()});
        };

        //Mouse Activity
        document.addEventListener('mousemove', throttle((e) => {
            analyticsData.activity.mouseMovements.push({ x: e.clientX, y: e.clientY, t: Date.now() });
            resetIdle();
        }, 200));

        //Clicks
        document.addEventListener('mousedown', (e) => {
            analyticsData.activity.clicks.push({x: e.clientX, y: e.clientY, button: e.button, t: Date.now()});
            resetIdle();
        });

        //Scrolling 
        document.addEventListener('scroll', throttle(() => {
            analyticsData.activity.scrolling.push({x: window.scrollX, y: window.scrollY, t: Date.now()});
            resetIdle();
        }, 200));

        // Keyboard Activity
        document.addEventListener('keydown', (e) => {
            analyticsData.activity.keyStrokes.push({ key: e.key, type: 'keydown', t: Date.now() });
            resetIdle();
        });
        document.addEventListener('keyup', (e) => {
            analyticsData.activity.keyStrokes.push({ key: e.key, type: 'keyup', t: Date.now() });
            resetIdle();
        });
    }


    // DATA TRANSMISSION
    function sendData(dataPayload, isFinal = false) {
        //user left page timestamp
        if (isFinal) {
            dataPayload.activity.leftPageAt = Date.now();
        }

        const payloadString = JSON.stringify(dataPayload);

        //using sendBeacon
        if (navigator.sendBeacon) {
            const blob = new Blob([payloadString], { type: 'application/json' });
            navigator.sendBeacon(ENDPOINT_URL, blob);
        } else {
            fetch(ENDPOINT_URL, {
                method: 'POST',
                body: payloadString,
                headers: { 'Content-Type': 'application/json' },
                keepalive: true
            });
        }

        
    }

    window.addEventListener('pagehide', () => {
        sendData(analyticsData, true);
    }, { capture: true });

    //INITALIZE <- I cant spell this word for some reason
    window.addEventListener('load', () => {
        collectStaticData();
        collectPerformanceData();
        setupActivityListeners();
        resetIdle();

        setInterval(() => sendData(analyticsData), 10000)
    });

})();