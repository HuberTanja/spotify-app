const swiper = document.querySelector('#swiper');
        const like = document.querySelector('#like');
        const dislike = document.querySelector('#dislike');

        // constants
        const albumData = [
            {
                artist: "Kendrick Lamar",
                coverUrl: "./img/gnx.jpeg",
                song: "peekaboo - (Kendrick Lamar)"
            },
            {
                artist: "Joji",
                coverUrl: "./img/joji.jpeg",
                song: "Slow Dancing in the Dark - (Joji)"
            },
            {
                artist: "XXXTentacion",
                coverUrl: "./img/xxx.jpeg",
                song: "Teeth - (XXXTentacion)"
            },
            {
                artist: "Still Woozy",
                coverUrl: "./img/still_woozy.jpeg",
                song: "Goodie Bag - (Still Woozy)"
            },
            {
                artist: "Mac Miller",
                coverUrl: "./img/mac_miller.jpeg",
                song: "Self Care - (Mac Miller)"
            }
        ];

        // variables
        let currentIndex = 0;

        // functions
        function appendNewCard() {
            if (currentIndex >= albumData.length) {
                currentIndex = 0;
            }

            const currentAlbum = albumData[currentIndex];
            const card = new Card({
                artist: currentAlbum.artist,
                song: currentAlbum.song,
                imageUrl: currentAlbum.coverUrl,
                onDismiss: loadNextCard,
                onLike: () => {
                    like.style.animationPlayState = 'running';
                    like.classList.toggle('trigger');
                },
                onDislike: () => {
                    dislike.style.animationPlayState = 'running';
                    dislike.classList.toggle('trigger');
                }
            });
            card.element.cardInstance = card;
            swiper.append(card.element);
            currentIndex++;
        }

        function loadNextCard() {
            appendNewCard();
        }

        function swipeRight() {
            const card = swiper.querySelector('.card:last-child');
            if (card) {
                const cardInstance = card.cardInstance;
                if (cardInstance) {
                    cardInstance.swipe(1);
                }
            }
        }

        function swipeLeft() {
            const card = swiper.querySelector('.card:last-child');
            if (card) {
                const cardInstance = card.cardInstance;
                if (cardInstance) {
                    cardInstance.swipe(-1);
                }
            }
        }

        // Load only the first card
        appendNewCard();