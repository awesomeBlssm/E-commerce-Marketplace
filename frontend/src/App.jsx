import { Outlet } from 'react-router-dom';
import Footer from './components/layout/Footer';
import Header from './components/layout/Header';
import styles from './App.module.css';

export default function App() {
  return (
    <>
      <Header />
      <main id="main-content" className={styles.main}>
        <Outlet />
      </main>
      <Footer />
    </>
  );
}
