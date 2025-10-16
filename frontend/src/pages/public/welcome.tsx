import { Head } from '@inertiajs/react';
import { PublicFooter } from './components/public-footer';
import { PublicHeader } from './components/public-header';
import { WelcomeHero } from './components/welcome/welcome-hero';
import { WelcomeSections } from './components/welcome/welcome-sections';

export default function Welcome() {
  return (
    <>
      <Head title="Bienvenido" />
      <div className="bg-background flex min-h-screen flex-col">
        <PublicHeader />
        <WelcomeHero />
        <WelcomeSections />
        <PublicFooter />
      </div>
    </>
  );
}
