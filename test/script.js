// DOM
const swiper = document.querySelector('#swiper');
const like = document.querySelector('#like');
const dislike = document.querySelector('#dislike');

// constants
const urls = [
  './img/gnx.jpeg',
  './img/joji.jpeg',
  './img/xxx.jpeg',
  './img/still_woozy.jpeg',
  './img/mac_miller.jpeg'
];

// variables
let currentIndex = 0;

// functions
function appendNewCard() {
  // Reset index if we've reached the end of the array
  if (currentIndex >= urls.length) {
    currentIndex = 0;
  }

  const card = new Card({
    imageUrl: urls[currentIndex],
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