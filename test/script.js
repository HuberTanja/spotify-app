// DOM
const swiper = document.querySelector('#swiper');
const like = document.querySelector('#like');
const dislike = document.querySelector('#dislike');

// constants
const albumData = [
  {
    artist: "Kendrick Lamar",
    coverUrl: "./img/gnx.jpeg"
  },
  {
    artist: "Joji",
    coverUrl: "./img/joji.jpeg"
  },
  {
    artist: "XXXTentacion",
    coverUrl: "./img/xxx.jpeg"
  },
  {
    artist: "Still Woozy",
    coverUrl: "./img/still_woozy.jpeg"
  },
  {
    artist: "Mac Miller",
    coverUrl: "./img/mac_miller.jpeg"
  }
];

// variables
let currentIndex = 0;

// functions
function appendNewCard() {
  // Reset index if we've reached the end of the array
  if (currentIndex >= albumData.length) {
    currentIndex = 0;
  }

  const currentAlbum = albumData[currentIndex];
  const card = new Card({
    artist: currentAlbum.artist,
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
  swiper.append(card.element);
  currentIndex++;
}

function loadNextCard() {
  appendNewCard();
}

// Load only the first card
appendNewCard();