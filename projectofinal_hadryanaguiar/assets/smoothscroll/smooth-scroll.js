(function () {
    // Configurações e variáveis globais
    var settings = {
        frameRate: 150,
        animationTime: 400,
        stepSize: 100,
        pulseAlgorithm: true,
        pulseScale: 4,
        pulseNormalize: 1,
        accelerationDelta: 50,
        accelerationMax: 3,
        keyboardSupport: true,
        arrowScroll: 50,
        fixedBackground: true,
        excluded: ""
    };

    // Variáveis de controle e estado
    var isScrolling = false,
        isFixedBackground = false,
        documentHeight = document.documentElement,
        activeElement,
        lastScrollTime = Date.now(),
        scrollBuffer = [],
        uniqueID = (function () {
            var id = 0;
            return function (element) {
                return element.uniqueID || (element.uniqueID = id++);
            };
        })();

    // Função principal que inicia o comportamento de rolagem suave
    function init() {
        if (!isScrolling && document.body) {
            isScrolling = true;
            activeElement = document.body;

            // Adiciona event listeners
            if (settings.keyboardSupport) window.addEventListener("keydown", onKeyDown, false);
            if (top != self) isFixedBackground = true;
            else if (documentHeight.scrollHeight > window.innerHeight) {
                // Cria um div para permitir a rolagem suave
                var div = document.createElement("div");
                div.style.cssText = "position:absolute; z-index:-10000; top:0; left:0; right:0; height:" + documentHeight.scrollHeight + "px";
                document.body.appendChild(div);
            }

            // Configura o anexo de background
            if (!settings.fixedBackground) {
                activeElement.style.backgroundAttachment = "scroll";
                document.documentElement.style.backgroundAttachment = "scroll";
            }
        }
    }

    // Função para controlar a rolagem suave
    function smoothScroll(target, deltaX, deltaY) {
        // Controle de aceleração
        if (settings.accelerationMax != 1) {
            var deltaTime = Date.now() - lastScrollTime;
            if (deltaTime < settings.accelerationDelta) {
                var factor = (1 + (50 / deltaTime)) / 2;
                if (factor > 1) {
                    factor = Math.min(factor, settings.accelerationMax);
                    deltaX *= factor;
                    deltaY *= factor;
                }
            }
            lastScrollTime = Date.now();
        }

        scrollBuffer.push({
            x: deltaX,
            y: deltaY,
            lastX: deltaX < 0 ? 0.99 : -0.99,
            lastY: deltaY < 0 ? 0.99 : -0.99,
            start: Date.now()
        });

        if (!isScrolling) {
            var scrollRoot = getScrollRoot();
            var isBody = target === scrollRoot || target === document.body;

            if (target.$scrollBehavior === undefined) {
                setScrollBehavior(target);
            }

            var step = function (timestamp) {
                var now = Date.now();
                var x = 0, y = 0;

                for (var i = 0; i < scrollBuffer.length; i++) {
                    var item = scrollBuffer[i];
                    var elapsedTime = now - item.start;
                    var finished = elapsedTime >= settings.animationTime;
                    var progress = finished ? 1 : elapsedTime / settings.animationTime;

                    if (settings.pulseAlgorithm) {
                        progress = pulse(progress);
                    }

                    var moveX = item.x * progress - item.lastX;
                    var moveY = item.y * progress - item.lastY;

                    x += moveX;
                    y += moveY;

                    item.lastX += moveX;
                    item.lastY += moveY;

                    if (finished) {
                        scrollBuffer.splice(i, 1);
                        i--;
                    }
                }

                if (isBody) {
                    window.scrollBy(x, y);
                } else {
                    if (x) target.scrollLeft += x;
                    if (y) target.scrollTop += y;
                }

                if (scrollBuffer.length) {
                    requestAnimationFrame(step);
                } else {
                    isScrolling = false;
                    if (target.$scrollBehavior !== undefined) {
                        target.style.scrollBehavior = target.$scrollBehavior;
                        target.$scrollBehavior = null;
                    }
                }
            };

            requestAnimationFrame(step);
            isScrolling = true;
        }
    }

    // Função para lidar com eventos de roda do mouse
    function onWheel(event) {
        init();

        var target = event.target;

        if (event.defaultPrevented || event.ctrlKey || isEmbeddable(target)) {
            return true;
        }

        var deltaX = -event.wheelDeltaX || event.deltaX || 0;
        var deltaY = -event.wheelDeltaY || event.deltaY || 0;

        if (!deltaX && !deltaY) {
            deltaY = -event.wheelDelta || 0;
        }

        if (event.deltaMode === 1) {
            deltaX *= 40;
            deltaY *= 40;
        }

        var scrollTarget = findScrollableElement(target);

        if (!scrollTarget) {
            return true;
        }

        smoothScroll(scrollTarget, deltaX, deltaY);

        event.preventDefault();
    }

    // Outras funções auxiliares...

    // Adiciona event listeners principais
    window.addEventListener("wheel", onWheel, { passive: false });
    window.addEventListener("mousedown", function (event) { activeElement = event.target; }, false);
    window.addEventListener("load", init, false);
})();
