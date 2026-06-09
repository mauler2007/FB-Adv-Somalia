document.addEventListener("DOMContentLoaded", function () {

    // gsap.registerPlugin(ScrollTrigger);
    const tl = gsap.timeline();
    const duration = 0.7;
    const delay = 1;

    const title = document.querySelector(".animated-page-title");
    //   const subtitle = document.querySelector(".top__title p");
    //   const text = document.querySelector(".top__text");
    const btnBlock = document.querySelector(".animated-page-content");
    const topDecorElement = document.querySelector(".animated-coin1");
    const topDecorElement2 = document.querySelector(".animated-coin2");
    const topDecorElement3 = document.querySelector(".animated-blue-star");
    const screen = document.querySelectorAll(".animated-image");
    const gradient = document.querySelector(".top__radialGardient");
    const percent = document.querySelector(".animated-page-accent-text");
    const freespin = document.querySelector(".title__fs");

    // const decor = document.querySelector(".top .decor");
    const isDesktopWidth = window.innerWidth < 1200 ? false : true;
    //   const gradient = document.querySelector(".top__radialGardient");

    const accentColor = '#F40F3E';

    function screnLeft() {
        const screenTimeLine = new TimelineMax();
        gsap.to(percent, {
            color: accentColor, duration: 0.5, delay: 0.8,
        })
        screenTimeLine
            .set(title, { opacity: 0, visibility: 'visible', })
            .set(btnBlock, { opacity: 0, visibility: 'visible', })
            .set(topDecorElement, { opacity: 0, visibility: 'visible', })
            .set(topDecorElement2, { opacity: 0, visibility: 'visible', })
            .set(topDecorElement3, { opacity: 0, visibility: 'visible', })
            .from(title, 2, {
                // змінено з 1 на 2
                opacity: 0,
                x: -500,
                ease: Elastic.easeOut.config(1, 1),
            })
            .from(
                btnBlock,
                2,
                {
                    // змінено з 1 на 2
                    opacity: 0,
                    x: -500,
                    ease: Elastic.easeOut.config(1, 1),
                },
                0
            )
            .from(
                topDecorElement,
                4,
                {
                    // змінено з 1 на 2
                    opacity: 0,
                    scale: 0,
                    // x: -200,
                    // y: -200,
                    rotate: -50,
                    ease: Elastic.easeOut.config(1, 1),
                    onComplete() {
                        const el = this.targets()[0];

                        const triTL = gsap.timeline({ repeat: -1, repeatDelay: 0 });

                        triTL
                            // Рух вправо
                            .to(el, {
                                x: 30,
                                rotationY: 25,
                                duration: 9,
                                ease: "sine.inOut",
                            })
                            // Вниз
                            .to(el, {
                                y: 40,
                                x: 50,
                                duration: 18,
                                rotationY: 0,
                                scale: 1.05,
                                ease: "sine.inOut",
                            })
                            // Назад до початку
                            .to(el, {
                                x: 0,
                                scale: 1,
                                y: 0,
                                duration: 9,
                                ease: "sine.inOut",
                            });
                    }
                },
                "-=1"
            )
            .from(
                topDecorElement2,
                4,
                {
                    opacity: 0,
                    scale: 0,
                    rotate: -50,
                    ease: Elastic.easeOut.config(1, 1),
                    onComplete() {
                        const el = this.targets()[0];

                        const loopTL = gsap.timeline({ repeat: -1 });
                        loopTL.to(el, {
                            x: 30,
                            duration: 9,
                            rotation: 15,
                            ease: "sine.inOut",
                        })
                            // Вниз
                            .to(el, {
                                y: 40,
                                x: 46,
                                duration: 18,
                                scale: 1.05,
                                ease: "sine.inOut",
                            })
                            // Назад до початку
                            .to(el, {
                                x: 0,
                                scale: 1,
                                rotation: 0,
                                y: 0,
                                duration: 9,
                                ease: "sine.inOut",
                            });

                    }
                },
                "-=0"
            )
            .from(
                topDecorElement3,
                4,
                {
                    // змінено з 1 на 2
                    opacity: 0,
                    scale: 0,
                    rotate: -50,
                    ease: Elastic.easeOut.config(1, 1),
                    onComplete() {
                        const el = this.targets()[0];

                        const loopTL = gsap.timeline({ repeat: -1 });
                        loopTL.to(el, {
                            x: 30,
                            y: -20,
                            duration: 11,
                            rotation: 15,
                            ease: "sine.inOut",
                        })
                            // Вниз
                            .to(el, {
                                y: 30,
                                duration: 28,
                                scale: 1.05,
                                ease: "sine.inOut",
                            })
                            // Назад до початку
                            .to(el, {
                                x: 0,
                                scale: 1,
                                rotation: 0,
                                y: 0,
                                duration: 8,
                                ease: "sine.inOut",
                            });
                    }
                },
                "-=3"
            );

        return screenTimeLine;
    }

    function sceneRight() {
        const screenTL = new TimelineMax();

        screenTL
            .set(screen, { opacity: 0, visibility: 'visible' })
            .set('.blobs', { opacity: 0, visibility: 'visible' })
            // .set('.parent', { opacity: 0, visibility: 'visible' })
            // .set('.light-glow', { opacity: 0, visibility: 'visible' })
            // .set('.light-glow2', { opacity: 0, visibility: 'visible' })
            .from(
                screen,
                5,
                {
                    opacity: 0,
                    x: "50vw",
                    ease: Elastic.easeOut.config(1, 0.9),
                    onComplete() {
                        const el = this.targets()[0];
                        gsap.from(el, {
                            keyframes: [
                                { rotation: 1, x: -2, y: 2, scale: 1.015, duration: 10 },
                                { rotation: -1, x: 2, y: -2, scale: 1.02, duration: 10 },
                                { rotation: 0, x: 0, y: 0, scale: 1.01, duration: 10 }
                            ],
                            ease: "sine.inOut",
                            repeat: -1,
                            yoyo: true
                        });
                        gsap.to('.blobs', {
                            opacity: 1,
                            duration: 0.4,
                            ease: "sine.inOut",
                        });
                        // gsap.to('.parent', {
                        //     opacity: 1,
                        //     duration: 4,
                        //     ease: "sine.inOut",
                        // });
                    }
                },
                0
            )

        return screenTL;
    }



    const galleryList = document.querySelector(".cards__inner");

    if (galleryList && isDesktopWidth) {
        const galleryItems = galleryList.querySelectorAll(".card");
        // console.log(galleryItems)

        function animHandler(entries, self) {
            // get array of the newly visible elements
            let targets = entries.map((entry) => {

                if (entry.isIntersecting) {
                    self.unobserve(entry.target);
                    entry.target.classList.add("animated");

                    // console.log(entry.target);
                    // console.log(entry.target._gsap);

                    if (entry.target) {
                        return entry.target;
                    }
                }
            });
            // console.log("targets:==>", targets);

            targets.forEach((target, i) => {
                if (!target) return;

                gsap.to(target, {
                    opacity: 1,
                    delay: (index) => {
                        index = i / 5;
                        return index;
                    },
                });
            });
        }

        const observer = new IntersectionObserver(animHandler, {
            root: null,
            threshold: 0.3,
        });

        // Add observe for each gallery item
        const initObserver = (arr) => {
            arr.forEach((item) => {
                observer.observe(item);
            });
        };
        // Проверка наличия элементов галереи
        if (galleryItems.length > 0) {
            gsap.set(galleryItems, { opacity: 0 });
            initObserver(galleryItems);
        }
    }

    // payments
    const paymentList = document.querySelector(".payments-methods");
    const paymentItems = document.querySelectorAll(".animated-payment-el");

    if (paymentList && paymentItems.length > 0) {

        const observer = new IntersectionObserver((entries, self) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    self.unobserve(entry.target);
                    gsap.set(paymentItems, { opacity: 1, visibility: 'visible', })
                    gsap.from(paymentItems, {
                        y: 40,
                        opacity: 0,
                        stagger: {
                            each: 0.05,
                            onComplete() {
                                // console.log('each el done');
                            }
                        },
                        // onComplete() {
                        //     console.log('whole tween scale done');
                        //     gsap.to(paymentItems, { 
                        //         scale: 1.2, duration: .2 
                        //     });
                        // }
                    });
                }
            });
        }, {
            threshold: 0.4,
        });

        observer.observe(paymentList);
    }

    // payments

    // instruction
    const instructionItems = document.querySelectorAll(".animated-instruction-item");

    if (instructionItems.length > 0) {

        const isMobile = window.innerWidth < 768;

        // Встановити початкові стилі
        gsap.set(instructionItems, { opacity: 0, y: 40, visibility: 'hidden' });

        const observer = new IntersectionObserver((entries, self) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    self.unobserve(entry.target); // щоб не анімувати повторно

                    entry.target.style.visibility = 'visible'; // виводимо

                    gsap.to(entry.target, {
                        y: 0,
                        opacity: 1,
                        duration: 0.8,
                        ease: "power2.out"
                    });
                }
            });
        }, {
            threshold: isDesktopWidth ? 0.8 : 0.2,
        });

        // Observe кожен пункт
        instructionItems.forEach(item => observer.observe(item));
    }
    // instruction



    //   function sceneGrad() {
    //     const screenTL = new TimelineMax();

    //     screenTL
    //       // .set(decor, { opacity: 1 })
    //       .from(
    //         gradient,
    //         4.5,
    //         {
    //           opacity: 0,
    //           scale: 3,
    //           // y: "50vw",
    //           ease: Elastic.easeOut.config(1, 0.9),
    //         },
    //        0
    //       )

    //     return screenTL;
    //   }

    window.onload = function () {
        tl.add(screnLeft(), 0.5);
        tl.add(sceneRight(), 0.8);
        // tl.add(sceneGrad(), 0);

        // gsap.from(gradient,{transform: scale(0),height: '100vh'},3)
    };

    function bindObserver(parentSel, childSel) {
        const galleryList = document.querySelector(parentSel);
        if (!galleryList) return;
        const galleryItems = galleryList.querySelectorAll(childSel);

        function animHandler(entries, self) {
            // get array of the newly visible elements
            let targets = entries.map((entry) => {

                if (entry.isIntersecting) {
                    self.unobserve(entry.target);
                    entry.target.classList.add("animated");

                    // console.log(entry.target);
                    // console.log(entry.target._gsap);

                    if (entry.target) {
                        return entry.target;
                    }
                }
            });
            // console.log("targets:==>", targets);

            targets.forEach((target, i) => {
                if (!target) return;

                gsap.to(target, {
                    opacity: 1,
                    delay: (index) => {
                        index = i / 100;
                        return index;
                    },
                });
            });
        }

        const observer = new IntersectionObserver(animHandler, {
            root: null,
            threshold: 0.3,
        });

        // Add observe for each gallery item
        const initObserver = (arr) => {
            arr.forEach((item) => {
                observer.observe(item);
            });
        };
        // Проверка наличия элементов галереи
        if (galleryItems.length > 0) {
            gsap.set(galleryItems, { opacity: 0 });
            initObserver(galleryItems);
        }
    }

    bindObserver('.who-are__slide', '.who-are__item');
    bindObserver('.advantages__slide', '.advantages__item');
    bindObserver('.operate-parent', '.operate-element');



});
