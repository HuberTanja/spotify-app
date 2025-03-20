const swiper = document.querySelector('#swiper');
const like = document.querySelector('#like');
const dislike = document.querySelector('#dislike');

// Funktionen
function appendNewCard() {
    if (currentIndex >= albumData.length) {
        currentIndex = 0;
    }

    const currentAlbum = albumData[currentIndex];
    const card = new Card({
        artist: currentAlbum.artist,
        song: currentAlbum.song,
        imageUrl: currentAlbum.coverUrl,
        onDismiss: () => {
            if (!isSongPlaying) { // Prüfen, ob ein Song bereits läuft
                isSongPlaying = true; // Verhindert mehrfaches Laden
                loadNextCard();
                setTimeout(() => { isSongPlaying = false; }, 500); // Verzögerung, um Mehrfachaufrufe zu verhindern
            }
        },
        onLike: () => {
            like.style.animationPlayState = 'running';
            like.classList.toggle('trigger');
        },
        onDislike: () => {
            dislike.style.animationPlayState = 'running';
            dislike.classList.toggle('trigger');
        }
    });

    card.element.style.zIndex = albumData.length - currentIndex; // Höherer Index für vordere Karten
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

// Lade nur die erste Karte beim Start
appendNewCard();