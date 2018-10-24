/* jQuery Orbit Plugin 1.2.3 * www.ZURB.com/playground * Copyright 2010, ZURB
 * Free to use under the MIT license. */
(function ($) {
    $.fn.orbit = function (options) {
        //Defaults to extend options
        var defaults = {
            animation: 'horizontal-push', 		// fade, horizontal-slide, vertical-slide, horizontal-push
            animationSpeed: 600, 				// how fast animtions are
            advanceSpeed: 4000, 				// if timer is enabled, time between transitions 
            directionalNav: true, 				// manual advancing directional navs
            frontendWidth: 150,		//frontend Img Width
            frontendLeft: 75,  //front left
            backendWidth: 120, //backend Img Width 1
            backendTop: 20,	//backend Img  1 Position
            backendLeft: 30, //backend Img  1 left
            backendLeft3: 150, //backend Img  1 rgiht
            backendWidth2: 90, //backend Img Width 2
            backendTop2: 40,	// backend Img Position 2      
            backendLeft4: 210,// backend Img Position right 
            backendLeft2: 0, //backend Img Position left
            backendOpacity: "0.8",
            afterSlideChange: function () { } 		// empty function 
        };
        //Extend those options
        var options = $.extend(defaults, options);
        return this.each(function () {
            //Global Variables
            var activeSlide = 2,
                numberSlides = 0,
                locked;
            //Initialize
            var orbit = $(this).addClass('orbit');
            //Collect all slides and set slider size of largest image
            var slides = orbit.find("li");
            numberSlides = slides.length;
            orbitWidth = options.frontendWidth;
            //Animation locking functions
            function unlock() {
                locked = false;
            }
            function lock() {
                locked = true;
            }
            if (slides.length == 1) {
                options.directionalNav = false;
                options.timer = false;
            }

            //Set initial front photo z-index and fades it in
            slides.eq(activeSlide)
              .css({ "z-index": numberSlides, left: options.frontendLeft, "width": options.frontendWidth, top: 0,"opacity":"1" })
              .fadeIn(function () {
                  slides.eq(activeSlide).siblings().css({ "width": options.backendWidth, top: options.backendTop });
              });
            slides.eq((activeSlide - 2) % numberSlides).css({ "left": options.backendLeft2, "z-index": numberSlides - 2, "top": options.backendTop2, "width": options.backendWidth2 });
            slides.eq((activeSlide - 1) % numberSlides).css({ "left": options.backendLeft, "z-index": numberSlides - 1, "top": options.backendTop, "width": options.backendWidth, "opacity": options.backendOpacity });
            slides.eq((activeSlide) % numberSlides).css({ "left": options.frontendLeft, "z-index": numberSlides, "width": options.frontendWidth, top: 0 });
            slides.eq((activeSlide + 1) % numberSlides).css({ "left": options.backendLeft3, "z-index": numberSlides - 1, "top": options.backendTop, "width": options.backendWidth, "opacity": options.backendOpacity });
            slides.eq((activeSlide + 2) % numberSlides).css({ "left": options.backendLeft4, "z-index": numberSlides - 2, "top": options.backendTop2, "width": options.backendWidth2 });

            for (act = activeSlide + 3; act < numberSlides; act++) {
                slides.eq(act % numberSlides).css({ "z-index": numberSlides - 3, "top": options.backendTop2 });
            }

            // ==================
            // ! DIRECTIONAL NAV   
            // ==================

            //DirectionalNav { rightButton --> shift("next"), leftButton --> shift("prev");
            if (options.directionalNav) {
                if (options.directionalNav == "false") { return false; }
                var leftBtn = $('.bd-lucky .popup2 .btn-pre'),
                  rightBtn = $('.bd-lucky .popup2 .btn-next');
                leftBtn.click(function () {
                    shift("prev");
                });
                rightBtn.click(function () {
                    shift("next")
                });
            }

            // ====================
            // ! SHIFT ANIMATIONS   
            // ====================

            //Animating the shift!
            function shift(direction) {
                //remember previous activeSlide
                var prevActiveSlide = activeSlide,
                    nextActiveSlide = activeSlide,
                    prevActiveSlide1 = activeSlide,
                    nextActiveSlide1 = activeSlide,
                    slideDirection = direction;
                //exit function if bullet clicked is same as the current image
                if (prevActiveSlide == slideDirection) { return false; }
                //reset Z & Unlock
                function resetAndUnlock() {
                    unlock();
                    options.afterSlideChange.call(this);
                }
                if (slides.length == "1") { return false; }
                if (!locked) {
                    lock();
                    //deduce the proper activeImage
                    if (direction == "next") {
                        activeSlide++
                        prevActiveSlide = (activeSlide - 1 + numberSlides) % numberSlides;
                        prevActiveSlide1 = (activeSlide - 2 + numberSlides) % numberSlides;
                        if (activeSlide == numberSlides) {
                            activeSlide = 0;
                        }
                        nextActiveSlide = (activeSlide + 1) % numberSlides;
                        nextActiveSlide1 = (activeSlide + 2) % numberSlides;
                    } else if (direction == "prev") {
                        activeSlide--
                        prevActiveSlide = (activeSlide + 1) % numberSlides;
                        prevActiveSlide1 = (activeSlide + 2) % numberSlides;
                        if (activeSlide < 0) {
                            activeSlide = numberSlides - 1;
                        }
                        nextActiveSlide = (activeSlide - 1 + numberSlides) % numberSlides;
                        nextActiveSlide1 = (activeSlide - 2 + numberSlides) % numberSlides;
                    } else {
                        activeSlide = direction;
                        if (prevActiveSlide1 < activeSlide) {
                            slideDirection = "next";
                        } else if (prevActiveSlide1 > activeSlide) {
                            slideDirection = "prev"
                        }
                    }

                    //push-over
                    if (options.animation == "horizontal-push") {
                        if (slideDirection == "next") {
                            slides
                                .eq(activeSlide)
                                .css({ "z-index": numberSlides })
                              .animate({ "left": options.frontendLeft, "width": options.frontendWidth, "top": "0", "opacity": "1" }, options.animationSpeed, resetAndUnlock);
                            slides
                               .eq(prevActiveSlide).css({ "z-index": numberSlides - 1 })
                               .animate({ "left": options.backendLeft, "width": options.backendWidth, top: options.backendTop, "opacity": options.backendOpacity }, options.animationSpeed);
                            slides
                                .eq(prevActiveSlide1).css({ "z-index": numberSlides - 2 })
                                .animate({ "left": "0px", "width": options.backendWidth2 + "px", top: options.backendTop2, "opacity": options.backendOpacity }, options.animationSpeed);
                            slides
                                .eq(nextActiveSlide).css({ "z-index": numberSlides - 1 })
                                .animate({ "left": options.backendLeft3, "width": options.backendWidth, top: options.backendTop, "opacity": options.backendOpacity }, options.animationSpeed);
                            slides
                               .eq(nextActiveSlide1).css({ "z-index": numberSlides - 2 })
                               .animate({ "left": options.backendLeft4, "width": options.backendWidth2, top: options.backendTop2, "opacity": options.backendOpacity }, options.animationSpeed);
                            if (numberSlides > 5) {
                                for (j = numberSlides - 5; j >= 1; j--) {
                                    slides.eq((prevActiveSlide1 - j + numberSlides) % numberSlides).css({ "z-index": numberSlides - 2 - j });
                                }
                            }
                        }
                        if (slideDirection == "prev") {
                            slides
                                .eq(activeSlide)
                                .css({ "z-index": numberSlides})
                                .animate({ "left": options.frontendLeft, "width": options.frontendWidth, top: "0", "opacity": "1" }, options.animationSpeed, resetAndUnlock);
                            slides
                                .eq(prevActiveSlide).css({ "z-index": numberSlides - 1 })
                                .animate({ "left": options.backendLeft3, "width": options.backendWidth, top: options.backendTop, "opacity": options.backendOpacity }, options.animationSpeed);
                            slides
                                .eq(prevActiveSlide1).css({ "z-index": numberSlides - 2 })
                                .animate({ "left": options.backendLeft4, "width": options.backendWidth2, top: options.backendTop2, "opacity": options.backendOpacity }, options.animationSpeed);
                            slides
                               .eq(nextActiveSlide1).css({ "z-index": numberSlides - 2 })
                               .animate({ "left": "0px", "width": options.backendWidth2, top: options.backendTop2, "opacity": options.backendOpacity }, options.animationSpeed);
                            slides
                               .eq(nextActiveSlide).css({ "z-index": numberSlides - 1 })
                               .animate({ "left": options.backendLeft, "width": options.backendWidth, top: options.backendTop, "opacity": options.backendOpacity }, options.animationSpeed);
                            if (numberSlides > 5) {
                                slides.eq((prevActiveSlide1 + 1 + numberSlides) % numberSlides).css({ "z-index": numberSlides - 4 });
                            }
                        }
                    }
                } //lock
            }//orbit function
        });//each call
    }//orbit plugin call
})(jQuery);
